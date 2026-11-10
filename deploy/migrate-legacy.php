<?php
declare(strict_types=1);

// CLI only; never expose database migration through the web installer.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/legacy-migration/Planner.php';

$args = getopt('', ['config:','apply','maintenance-confirmed','help']);
if (isset($args['help'])) {
    echo "php deploy/migrate-legacy.php --config=path/to/config.php [--apply --maintenance-confirmed]\n";
    echo "Default: read-only dry-run. Exit 0=ready/already applied, 2=blocked, 1=runtime failure.\n";
    exit;
}
$db = null; $lock = false; $applying = isset($args['apply']);
try {
    if (!isset($args['config']) || !is_file($args['config'])) throw new RuntimeException('Provide --config pointing to a private PHP config file.');
    $config = require $args['config'];
    if (!is_array($config)) throw new RuntimeException('Configuration must return an array.');
    foreach (['source_database','target_database'] as $key) if (!preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/', $config[$key] ?? '')) throw new RuntimeException("Invalid $key");
    $source = $config['source_database']; $target = $config['target_database'];
    if (strtolower($source) === strtolower($target) || in_array(strtolower($target),['mysql','sys','information_schema','performance_schema'],true)) throw new RuntimeException('Source and target must be distinct application databases.');
    if ($applying && !isset($args['maintenance-confirmed'])) throw new RuntimeException('Apply requires --maintenance-confirmed after stopping writes to BOTH applications and making backups.');
    $localPath = dirname(__DIR__) . '/backend/src/config/database.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    $host = $config['host'] ?? getenv('DB_HOST') ?: ($local['DB_HOST'] ?? '127.0.0.1');
    $port = (string)($config['port'] ?? getenv('DB_PORT') ?: ($local['DB_PORT'] ?? '3306'));
    if (!preg_match('/\A[A-Za-z0-9_.:-]+\z/',$host) || !ctype_digit($port) || (int)$port < 1 || (int)$port>65535) throw new RuntimeException('Invalid host/port.');
    $user = getenv('MIGRATION_DB_USER'); $pass = getenv('MIGRATION_DB_PASS');
    if ($user === false) $user = $local['DB_USER'] ?? '';
    if ($pass === false) $pass = $local['DB_PASS'] ?? '';
    $db = new PDO("mysql:host=$host;port=$port;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $db->exec("SET SESSION time_zone = '+07:00'");
    $db->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $db->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $sourceTables = ['user','profile','fname','dep','group','ven_name','ven_name_sub','ven_user','ven_com','ven','ven_change','sign_name','line'];
    $targetTables = ['user','profile','ven_name','ven_name_sub','ven_user','ven_com','ven_schedule','ven_change','ven_time','system_settings','agency_settings'];
    $meta = $db->prepare('SELECT TABLE_SCHEMA,TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA IN (?,?)');
    $meta->execute([$source,$target]); $engines = [];
    foreach ($meta as $r) $engines[$r['TABLE_SCHEMA']][$r['TABLE_NAME']] = $r['ENGINE'];
    foreach ([$source=>$sourceTables,$target=>$targetTables] as $schema=>$tables) foreach ($tables as $table) {
        if (!isset($engines[$schema][$table])) throw new RuntimeException("Required table missing: $schema.$table");
        if (strtoupper($engines[$schema][$table]) !== 'INNODB') throw new RuntimeException("Transactional InnoDB table required: $schema.$table");
    }
    $lockName = 'legacy-v1:' . $target;
    if (strlen($lockName)>64) $lockName = hash('sha256',$lockName);
    if ($applying) {
        $stmt = $db->prepare('SELECT GET_LOCK(?,0)'); $stmt->execute([$lockName]);
        if ((int)$stmt->fetchColumn() !== 1) throw new RuntimeException('Another migration is running.');
        $lock = true;
    }
    if (isset($engines[$target]['_legacy_migration_runs'])) {
        $stmt = $db->prepare("SELECT source_hash,applied_at,summary FROM `$target`.`_legacy_migration_runs` WHERE source_database=?"); $stmt->execute([$source]);
        if ($done = $stmt->fetch()) {
            echo json_encode(['status'=>'already_applied','source'=>$source,'target'=>$target,'applied_at'=>$done['applied_at'],'note'=>'One-time import already committed. No records changed. Use a fresh staging target for another rehearsal.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),"\n";
            exit(0);
        }
    }
    // DDL has implicit commits in MySQL. Create only empty bookkeeping tables before DML.
    if ($applying) {
        $db->exec("CREATE TABLE IF NOT EXISTS `$target`.`_legacy_migration_runs` (source_database varchar(64) NOT NULL PRIMARY KEY, source_hash char(64) NOT NULL, applied_at datetime NOT NULL, summary longtext NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS `$target`.`_legacy_migration_map` (source_database varchar(64) NOT NULL, entity varchar(64) NOT NULL, source_id varchar(255) NOT NULL, target_id varchar(255) NOT NULL, PRIMARY KEY(source_database,entity,source_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS `$target`.`_legacy_migration_archive` (source_database varchar(64) NOT NULL, source_table varchar(64) NOT NULL, source_id varchar(255) NOT NULL, payload longtext NOT NULL, PRIMARY KEY(source_database,source_table,source_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $db->beginTransaction();
    $old = []; $current = [];
    foreach ($sourceTables as $table) $old[$table] = $db->query("SELECT * FROM `$source`.`$table` ORDER BY " . ($table === 'ven_user' ? '`vu_id`' : '`id`'))->fetchAll();
    foreach ($targetTables as $table) $current[$table] = $db->query("SELECT * FROM `$target`.`$table` ORDER BY " . ($table === 'profile' ? '`user_id`' : '`id`') . ($applying ? ' FOR UPDATE' : ''))->fetchAll();
    // Corrections affect this import only. The legacy DB and archived originals stay unchanged.
    $corrected = $old; $correctionCount = 0;
    foreach ($config['row_overrides'] ?? [] as $table=>$overrides) {
        if (!isset($corrected[$table])) throw new RuntimeException("Unknown override table:$table");
        $pk = $table === 'ven_user' ? 'vu_id' : 'id';
        foreach ($overrides as $id=>$fields) {
            $found = false;
            foreach ($corrected[$table] as &$row) if ((string)$row[$pk] === (string)$id) {
                $found = true;
                foreach ($fields as $key=>$value) {
                    if ($key === $pk || !array_key_exists($key,$row) || in_array($key,['password_hash','auth_key','password_reset_token','token'],true)) throw new RuntimeException("Invalid override field:$table.$key");
                    $row[$key] = $value; $correctionCount++;
                }
                break;
            }
            unset($row);
            if (!$found) throw new RuntimeException("Override record not found:$table:$id");
        }
    }
    $planner = new LegacyMigrationPlanner();
    $config['source_database'] = $source;
    $planner->build($corrected,$current,$config);
    // Compare usernames using the target column collation, including accents/trailing spaces.
    $check = $db->prepare("SELECT COUNT(*) FROM `$target`.`user` WHERE username=?");
    $compare = $db->prepare("SELECT CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
    $names = [];
    foreach ($planner->rows['user'] ?? [] as $row) {
        $check->execute([$row['username']]);
        if ((int)$check->fetchColumn()>0) $planner->errors[] = 'Target username collision for mapped user:' . $row['id'] . ' (set username_overrides by legacy ID)';
        foreach ($names as $name) { $compare->execute([$name,$row['username']]); if ((int)$compare->fetchColumn() === 1) $planner->errors[] = 'Source usernames collide under target collation; set username_overrides.'; }
        $names[] = $row['username'];
    }
    // Validate planned columns/lengths even in dry-run, without inserting anything.
    $cols = $db->prepare('SELECT TABLE_NAME,COLUMN_NAME,CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=?');
    $cols->execute([$target]); $limits = [];
    foreach ($cols as $r) $limits[$r['TABLE_NAME']][$r['COLUMN_NAME']] = $r['CHARACTER_MAXIMUM_LENGTH'];
    foreach ($planner->rows as $table=>$rows) foreach ($rows as $row) foreach ($row as $column=>$value) {
        if (!array_key_exists($column,$limits[$table] ?? [])) { $planner->errors[] = "Target schema missing $table.$column"; continue; }
        $limit = $limits[$table][$column];
        $length = is_string($value) ? preg_match_all('/./us',$value) : 0;
        if ($limit !== null && $length > (int)$limit) $planner->errors[] = "Value exceeds target length $table.$column, row:" . ($row['id'] ?? 'profile');
    }
    $summary = ['status'=>$planner->errors ? 'blocked' : ($applying ? 'ready_to_apply' : 'ready'), 'mode'=>$applying ? 'apply' : 'dry-run',
        'source'=>$source, 'target'=>$target, 'source_counts'=>array_map('count',$old), 'planned_counts'=>array_map('count',$planner->rows), 'reviewed_field_corrections'=>$correctionCount,
        'errors'=>array_values(array_unique($planner->errors)), 'warnings'=>array_values(array_unique($planner->warnings))];
    $reviewedConfig = array_intersect_key($config,array_flip(['source_database','target_database','username_overrides','shift_periods','restore_subs','row_overrides','preserve_locked_pending','avatar_overrides','agency_settings']));
    $summary['config_hash'] = hash('sha256',serialize($reviewedConfig));
    if ($planner->errors || !$applying) {
        $db->rollBack();
        echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),"\n";
        exit($planner->errors ? 2 : 0);
    }
    // Existing operational rows are never updated; only settings id=1 are merged explicitly.
    foreach ($planner->rows as $table=>$rows) foreach ($rows as $row) {
        $columns = array_keys($row);
        if ($table === 'system_settings' || ($table === 'agency_settings' && !empty($current['agency_settings']))) {
            $id = $row['id']; unset($row['id']);
            $stmt = $db->prepare("UPDATE `$target`.`$table` SET " . implode(',',array_map(static fn($k)=>"`$k`=?",array_keys($row))) . ' WHERE id=?');
            $stmt->execute([...array_values($row),$id]);
        } else {
            $stmt = $db->prepare("INSERT INTO `$target`.`$table` (`" . implode('`,`',$columns) . '`) VALUES (' . implode(',',array_fill(0,count($columns),'?')) . ')');
            $stmt->execute(array_values($row));
        }
    }
    $stmt = $db->prepare("INSERT INTO `$target`.`_legacy_migration_map` (source_database,entity,source_id,target_id) VALUES (?,?,?,?)");
    foreach ($planner->maps as $entity=>$map) foreach ($map as $from=>$to) $stmt->execute([$source,$entity,(string)$from,(string)$to]);
    $stmt = $db->prepare("INSERT INTO `$target`.`_legacy_migration_archive` (source_database,source_table,source_id,payload) VALUES (?,?,?,?)");
    foreach ($old as $table=>$rows) foreach ($rows as $row) {
        $key = $row[$table === 'ven_user' ? 'vu_id' : 'id'];
        // These secrets are not needed to read historical documents. Hashes exist in migrated user rows.
        foreach (['password_hash','auth_key','password_reset_token','token'] as $secret) unset($row[$secret]);
        $stmt->execute([$source,$table,(string)$key,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
    $stmt->execute([$source,'_reviewed_configuration','1',json_encode($reviewedConfig,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    // Verify inserted IDs and nullable user references before committing the entire import.
    foreach ($planner->rows as $table=>$rows) {
        $key = $table === 'profile' ? 'user_id' : 'id';
        $stmt = $db->prepare("SELECT COUNT(*) FROM `$target`.`$table` WHERE `$key`=?");
        foreach ($rows as $row) { $stmt->execute([$row[$key]]); if ((int)$stmt->fetchColumn()!==1) throw new RuntimeException("Post-insert validation failed:$table"); }
    }
    $summary['status'] = 'applied';
    $stmt = $db->prepare("INSERT INTO `$target`.`_legacy_migration_runs` (source_database,source_hash,applied_at,summary) VALUES (?,?,NOW(),?)");
    $stmt->execute([$source,hash('sha256',serialize($old)),json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    $db->commit();
    echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),"\n";
} catch (Throwable $e) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    // PDO messages can include private field values: only disclose SQLSTATE.
    fwrite(STDERR, $e instanceof PDOException ? 'Database error (SQLSTATE ' . $e->getCode() . "). Import transaction rolled back if started.\n" : $e->getMessage() . "\n");
    exit(1);
} finally {
    if ($lock && $db instanceof PDO) { $stmt = $db->prepare('SELECT RELEASE_LOCK(?)'); $stmt->execute([$lockName]); }
}
