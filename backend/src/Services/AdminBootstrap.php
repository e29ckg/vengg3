<?php
declare(strict_types=1);
require_once __DIR__ . '/SessionSecurity.php';

function bootstrapAdministrator(PDO $db, string $password, string $username = 'admin'): bool
{
    if ((int)$db->query('SELECT COUNT(*) FROM user WHERE role = 9')->fetchColumn() > 0) {
        return false;
    }
    if (!SessionSecurity::passwordAllowed($password)) {
        throw new RuntimeException('รหัสผ่าน admin ต้องอยู่ระหว่าง 12-72 bytes');
    }
    $existing = $db->prepare('SELECT COUNT(*) FROM user WHERE username = ?');
    $existing->execute([$username]);
    if ((int)$existing->fetchColumn() > 0) {
        throw new RuntimeException('ชื่อ admin มีอยู่แล้ว กรุณาตรวจสิทธิ์บัญชีเดิมในระบบ');
    }
    $db->beginTransaction();
    try {
        $id = bin2hex(random_bytes(16));
        $stmt = $db->prepare('INSERT INTO user (id, username, password_hash, role, status, created_at) VALUES (?, ?, ?, 9, 10, NOW())');
        $stmt->execute([$id, $username, password_hash($password, PASSWORD_DEFAULT)]);
        $stmt = $db->prepare('INSERT INTO profile (user_id, first_name, last_name, srt) VALUES (?, ?, ?, 1)');
        $stmt->execute([$id, 'Administrator', 'Account']);
        $db->commit();
        return true;
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
}
