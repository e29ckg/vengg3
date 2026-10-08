<?php
declare(strict_types=1);

// CLI-only maintenance command. It never prints the token or database password.
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['--check', '--apply'], true) || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php clear_telegram_token.php --check|--apply\n");
    exit(2);
}

try {
    $localPath = __DIR__ . '/../src/config/database.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) throw new RuntimeException('Invalid database configuration');
    $host = getenv('DB_HOST') ?: ($local['DB_HOST'] ?? 'localhost');
    $port = getenv('DB_PORT') ?: ($local['DB_PORT'] ?? '3306');
    $name = getenv('DB_NAME') ?: ($local['DB_NAME'] ?? 'vengg_db');
    $user = getenv('DB_USER') ?: ($local['DB_USER'] ?? '');
    $password = getenv('DB_PASS') ?: ($local['DB_PASS'] ?? '');
    if (!is_string($host) || !preg_match('/\A[A-Za-z0-9_.:-]+\z/', $host) ||
        !is_string($name) || !preg_match('/\A[A-Za-z0-9_]{1,64}\z/', $name) ||
        !ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535 ||
        !is_string($user) || $user === '' || !is_string($password) || $password === '') {
        throw new RuntimeException('Incomplete database configuration');
    }

    $db = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    if ($argv[1] === '--apply') {
        $db->beginTransaction();
        $db->exec("UPDATE telegram_settings SET bot_token = '' WHERE id = 1");
    }
    $state = $db->query('SELECT CHAR_LENGTH(bot_token) FROM telegram_settings WHERE id = 1')->fetchColumn();
    if ($state === false) throw new RuntimeException('Telegram settings row missing');
    if ($argv[1] === '--apply') $db->commit();
    echo ((int)$state === 0 ? 'Telegram token is blank' : 'Telegram token is present') . PHP_EOL;
    exit((int)$state === 0 ? 0 : 1);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR, 'Could not verify or clear Telegram token (code ' . (string)$error->getCode() . ").\n");
    exit(3);
}
