<?php
// Used by deploy-xampp.ps1. Never print credentials or connection errors.
if (PHP_SAPI !== 'cli' || !in_array($argc, [2, 3], true) || ($argc === 3 && $argv[2] !== '--admin')) {
    exit(2);
}
$configPath = $argv[1];
if (!is_file($configPath)) {
    fwrite(STDERR, "Missing database.local.php\n");
    exit(2);
}
try {
    $config = require $configPath;
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('Incomplete database configuration');
        }
    }
    $pdo = new PDO(
        'mysql:host=' . $config['DB_HOST'] . ';port=' . ($config['DB_PORT'] ?? '3306') . ';dbname=' . $config['DB_NAME'] . ';charset=utf8mb4',
        $config['DB_USER'],
        $config['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->query('SELECT 1 FROM user LIMIT 1');
    if ($argc === 3 && (int)$pdo->query('SELECT COUNT(*) FROM user WHERE role = 9 AND status = 10')->fetchColumn() === 0) {
        throw new RuntimeException('No active administrator');
    }
    fwrite(STDOUT, "Database connection and schema OK\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Database connection or schema failed; check database.local.php and import database.sql.\n");
    exit(1);
}
