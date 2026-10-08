<?php
declare(strict_types=1);

// Called only by the localhost installer after its CSRF and installation lock checks.
function setupDatabase(array $input, string $projectRoot): void
{
    foreach (['mode', 'db_host', 'db_port', 'db_name', 'db_user', 'db_password', 'admin_user', 'admin_password'] as $key) {
        if (isset($input[$key]) && (!is_scalar($input[$key]) || strlen((string)$input[$key]) > 4096)) {
            throw new RuntimeException('ข้อมูลตั้งค่าฐานข้อมูลไม่ถูกต้อง');
        }
    }
    $mode = (string)($input['mode'] ?? 'connect');
    $host = (string)($input['db_host'] ?? '127.0.0.1');
    $port = filter_var($input['db_port'] ?? 3306, FILTER_VALIDATE_INT);
    $name = (string)($input['db_name'] ?? '');
    $user = (string)($input['db_user'] ?? '');
    $password = (string)($input['db_password'] ?? '');
    if (!in_array($mode, ['create', 'connect'], true) ||
        !in_array($host, ['127.0.0.1', 'localhost'], true) || !$port || $port < 1 || $port > 65535 ||
        !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]{0,63}\z/', $name) ||
        !preg_match('/\A[a-zA-Z][a-zA-Z0-9_]{0,31}\z/', $user) ||
        in_array(strtolower($name), ['mysql', 'sys', 'information_schema', 'performance_schema'], true) ||
        in_array(strtolower($user), ['root', 'mysql', 'mariadb.sys', 'mysqladmin'], true) || strlen($password) < 12) {
        throw new RuntimeException('กรอกชื่อฐานข้อมูลและบัญชีแอปให้ถูกต้อง รหัสผ่านแอปต้องมีอย่างน้อย 12 ตัวอักษร และใช้บัญชีอื่นนอกจาก root');
    }
    $configDir = $projectRoot . '/backend/src/config';
    if (!is_dir($configDir) || !is_writable($configDir)) {
        throw new RuntimeException('โฟลเดอร์ตั้งค่าฐานข้อมูลเขียนไม่ได้ กรุณาตรวจสิทธิ์ของ Apache');
    }
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5];
    if ($mode === 'create') {
        $schema = @file_get_contents($projectRoot . '/database.sql');
        if ($schema === false) {
            throw new RuntimeException('ไม่พบไฟล์ database.sql สำหรับสร้างโครงสร้าง');
        }
        // The repository schema contains DROP statements for Docker bootstrap.
        // The web installer only creates a brand new database and never runs them.
        $schema = preg_replace('/^DROP TABLE IF EXISTS `[^`]+`;\s*$/m', '', $schema);
        try {
            $admin = new PDO($dsn, (string)($input['admin_user'] ?? ''), (string)($input['admin_password'] ?? ''), $options);
            $exists = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $exists->execute([$name]);
            if ((int)$exists->fetchColumn() !== 0) {
                throw new RuntimeException('ชื่อฐานข้อมูลนี้มีอยู่แล้ว กรุณาเลือกเชื่อมฐานข้อมูลเดิมหรือใช้ชื่อใหม่');
            }
            $account = $admin->prepare("SELECT COUNT(*) FROM mysql.user WHERE User = ? AND Host = 'localhost'");
            $account->execute([$user]);
            if ((int)$account->fetchColumn() !== 0) {
                throw new RuntimeException('บัญชีแอปนี้มีอยู่แล้ว กรุณาใช้ชื่อบัญชีใหม่');
            }
        } catch (PDOException $error) {
            throw new RuntimeException('เชื่อมต่อ MySQL ด้วยบัญชีผู้ดูแลไม่ได้ กรุณาเปิด MySQL และตรวจบัญชีหรือรหัสผ่าน');
        }
        try {
            $admin->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $admin->exec("USE `$name`");
            $admin->exec($schema);
            $accountSql = $admin->quote($user) . "@'localhost'";
            $admin->exec('CREATE USER ' . $accountSql . ' IDENTIFIED BY ' . $admin->quote($password));
            $admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP ON `$name`.* TO $accountSql");
        } catch (PDOException $error) {
            throw new RuntimeException('สร้างฐานข้อมูลไม่สำเร็จ ตรวจสิทธิ์ผู้ดูแล MySQL; อาจมีฐานข้อมูลที่สร้างบางส่วนแล้ว กรุณาตรวจสอบก่อนลองอีกครั้ง');
        }
    }
    try {
        $app = new PDO($dsn . ";dbname=$name", $user, $password, $options);
        $app->query('SELECT 1 FROM user LIMIT 1');
    } catch (PDOException $error) {
        throw new RuntimeException('บัญชีแอปเชื่อมฐานข้อมูลไม่ได้หรือยังไม่มีตาราง user กรุณาตรวจค่าและโครงสร้างฐานข้อมูล');
    }
    $config = ['DB_HOST' => $host, 'DB_PORT' => (string)$port, 'DB_NAME' => $name, 'DB_USER' => $user, 'DB_PASS' => $password];
    $temporary = tempnam($configDir, 'db-setup-');
    if ($temporary === false) {
        throw new RuntimeException('สร้างไฟล์ตั้งค่าชั่วคราวไม่ได้');
    }
    try {
        if (file_put_contents($temporary, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX) === false ||
            !rename($temporary, $configDir . '/database.local.php')) {
            throw new RuntimeException('บันทึก database.local.php ไม่สำเร็จ');
        }
    } finally {
        if (is_file($temporary)) { unlink($temporary); }
    }
}
