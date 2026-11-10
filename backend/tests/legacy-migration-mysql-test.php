<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('MIGRATION_TEST_ALLOW_CREATE_DB') !== '1') exit("Set MIGRATION_TEST_ALLOW_CREATE_DB=1 to create isolated test databases.\n");
require_once __DIR__ . '/legacy-migration-test.php';
migrationUnitTests();
$db = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4',getenv('MIGRATION_DB_USER') ?: 'root',getenv('MIGRATION_DB_PASS') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$suffix = bin2hex(random_bytes(6));
$from = 'vengg_migration_test_src_' . $suffix; $to = 'vengg_migration_test_dst_' . $suffix;
$file = tempnam(sys_get_temp_dir(),'vengg-migration-test-');
try {
    [$src,$target,$config] = migrationFixture();
    foreach ([$from,$to] as $name) $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // Copy table definitions only, never production rows. All inserted data is synthetic.
    foreach ($src as $table=>$rows) {
        $db->exec("CREATE TABLE `$from`.`$table` LIKE `vengg`.`$table`");
        foreach ($rows as $row) {
            $cols=array_keys($row);
            $stmt=$db->prepare("INSERT INTO `$from`.`$table` (`".implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');
            $stmt->execute(array_values($row));
        }
    }
    $db->exec("USE `$to`");
    $schema=file_get_contents(__DIR__ . '/../../database.sql');
    $db->exec($schema);
    $config['source_database']=$from; $config['target_database']=$to;
    file_put_contents($file,"<?php\nreturn " . var_export($config,true) . ";\n");
    $run = static function(array $flags=[]) use ($file): array {
        $pipes=[];
        $proc=proc_open([PHP_BINARY,__DIR__.'/../../deploy/migrate-legacy.php','--config='.$file,...$flags],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        return [proc_close($proc),$out,$err];
    };
    [$code,$out,$err]=$run(); migrationCheck($code===0,'Dry run failed:'.$out.$err);
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.user")->fetchColumn()===0,'Dry run wrote application rows');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$to' AND TABLE_NAME='_legacy_migration_runs'")->fetchColumn()===0,'Dry run created metadata');
    [$code]=$run(['--apply']); migrationCheck($code===1,'Apply must require maintenance acknowledgement');
    $db->exec("CREATE TRIGGER `$to`.fail_import BEFORE INSERT ON `$to`.ven_schedule FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture rollback test'");
    [$code,$out,$err]=$run(['--apply','--maintenance-confirmed']); migrationCheck($code===1,'Forced failure did not fail');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.user")->fetchColumn()===0,'Failed import left partial user data');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.ven_com")->fetchColumn()===0,'Failed import left partial commands');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`._legacy_migration_runs")->fetchColumn()===0,'Failed run marked complete');
    $db->exec("DROP TRIGGER `$to`.fail_import");
    [$code,$out,$err]=$run(['--apply','--maintenance-confirmed']); migrationCheck($code===0,'Apply failed:'.$out.$err);
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.ven_schedule")->fetchColumn()===2,'Unexpected duty count');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.ven_change WHERE status=0")->fetchColumn()===1,'Pending state mismatch');
    $pending=$db->query("SELECT s.user_id,c.user2_id,s.status FROM `$to`.ven_change c JOIN `$to`.ven_schedule s ON c.s1_id=s.id WHERE c.status=0")->fetch();
    migrationCheck($pending['user_id']===$pending['user2_id'] && (int)$pending['status']===2,'Pending cancellation would restore wrong owner');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`._legacy_migration_archive WHERE source_table='ven_change'")->fetchColumn()===3,'Original cancelled history missing');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$from`.ven")->fetchColumn()===6,'Source changed');
    [$code,$out,$err]=$run(['--apply','--maintenance-confirmed']); migrationCheck($code===0 && (json_decode($out,true)['status'] ?? '')==='already_applied','Repeat run not idempotent');
    migrationCheck((int)$db->query("SELECT COUNT(*) FROM `$to`.ven_schedule")->fetchColumn()===2,'Repeated run duplicated data');
    require_once __DIR__.'/../src/Models/SettingModel.php';
    $db->exec("UPDATE `$to`.ven_name SET dn='nightCourt(16.30-20.30)'");
    $person=$db->query("SELECT user1_id FROM `$to`.ven_change ORDER BY id LIMIT 1")->fetchColumn();
    $history=(new SettingModel($db))->getUserChangeHistory($person);
    migrationCheck((bool)$history && $history[0]['ven_time_text']==='16.30 - 20.30' && $history[0]['ven_time']==='16:30:00','History ignored configured duty period');
    echo "PASS: MySQL dry-run, maintenance guard, rollback, commit, archive and repeat-run\n";
} finally {
    foreach ([$from,$to] as $name) {
        if (!preg_match('/\Avengg_migration_test_(src|dst)_[a-f0-9]{12}\z/',$name)) throw new RuntimeException('Unsafe test database cleanup');
        $db->exec("DROP DATABASE IF EXISTS `$name`");
    }
    if (is_file($file)) unlink($file);
}
