<?php
// Run once after importing database.sql. Do not pass the password as a shell argument.
require_once __DIR__ . '/../src/config/database.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$username = getenv('ADMIN_USERNAME') ?: 'admin';
$password = getenv('ADMIN_PASSWORD');
if (!$password || strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_PASSWORD to at least 12 characters.\n");
    exit(1);
}

$db = (new Database())->getConnection();
$count = (int)$db->query('SELECT COUNT(*) FROM user WHERE role = 9')->fetchColumn();
if ($count !== 0) {
    fwrite(STDERR, "An administrator already exists.\n");
    exit(1);
}

$id = bin2hex(random_bytes(16));
$db->beginTransaction();
try {
    $stmt = $db->prepare('INSERT INTO user (id, username, password_hash, role, status, created_at) VALUES (?, ?, ?, 9, 10, NOW())');
    $stmt->execute([$id, $username, password_hash($password, PASSWORD_DEFAULT)]);
    $stmt = $db->prepare('INSERT INTO profile (user_id, first_name, last_name, srt) VALUES (?, ?, ?, 1)');
    $stmt->execute([$id, 'Administrator', 'Account']);
    $db->commit();
    fwrite(STDOUT, "Administrator created: {$username}\n");
} catch (Throwable $e) {
    $db->rollBack();
    error_log('Administrator bootstrap failed: ' . $e->getMessage());
    fwrite(STDERR, "Could not create administrator.\n");
    exit(1);
}
