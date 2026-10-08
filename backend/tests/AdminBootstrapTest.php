<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Services/AdminBootstrap.php';

final class AdminBootstrapTest extends TestCase
{
    private function database(bool $withProfile = true): PDO
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->sqliteCreateFunction('NOW', static fn(): string => '2026-10-08 00:00:00');
        $db->exec('CREATE TABLE user (id TEXT PRIMARY KEY, username TEXT UNIQUE, password_hash TEXT, role INTEGER, status INTEGER, created_at TEXT)');
        if ($withProfile) {
            $db->exec('CREATE TABLE profile (user_id TEXT, first_name TEXT, last_name TEXT, srt INTEGER)');
        }
        return $db;
    }

    public function testCreatesActiveHighestPrivilegeAdminWithHashedPassword(): void
    {
        $db = $this->database();
        $password = bin2hex(random_bytes(16));
        self::assertTrue(bootstrapAdministrator($db, $password));
        $user = $db->query('SELECT * FROM user')->fetch(PDO::FETCH_ASSOC);
        self::assertSame('admin', $user['username']);
        self::assertSame(9, (int)$user['role']);
        self::assertSame(10, (int)$user['status']);
        self::assertTrue(password_verify($password, $user['password_hash']));
        self::assertNotSame($password, $user['password_hash']);
        self::assertSame($user['id'], $db->query('SELECT user_id FROM profile')->fetchColumn());
    }

    public function testExistingAdministratorIsNotReset(): void
    {
        $db = $this->database();
        bootstrapAdministrator($db, bin2hex(random_bytes(16)));
        $before = $db->query('SELECT * FROM user')->fetch(PDO::FETCH_ASSOC);
        self::assertFalse(bootstrapAdministrator($db, ''));
        self::assertSame($before, $db->query('SELECT * FROM user')->fetch(PDO::FETCH_ASSOC));
        self::assertSame(1, (int)$db->query('SELECT COUNT(*) FROM user')->fetchColumn());
    }

    public function testProfileFailureRollsBackUserCreation(): void
    {
        $db = $this->database(false);
        try {
            bootstrapAdministrator($db, bin2hex(random_bytes(16)));
            self::fail('Missing profile table should fail');
        } catch (PDOException $error) {
            self::assertSame(0, (int)$db->query('SELECT COUNT(*) FROM user')->fetchColumn());
        }
    }

    public function testDoesNotPromoteExistingUsername(): void
    {
        $db = $this->database();
        $db->exec("INSERT INTO user (id, username, role, status) VALUES ('existing', 'admin', 1, 10)");
        try {
            bootstrapAdministrator($db, bin2hex(random_bytes(16)));
            self::fail('Existing username should not be promoted');
        } catch (RuntimeException $error) {
            self::assertSame(1, (int)$db->query('SELECT role FROM user')->fetchColumn());
        }
    }
}
