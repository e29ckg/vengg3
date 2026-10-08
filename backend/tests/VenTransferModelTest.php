<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Models/VenTransferModel.php';

class SqliteLockingPdo extends PDO {
    public function prepare($query, $options = []): PDOStatement|false {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}

final class VenTransferModelTest extends TestCase {
    private SqliteLockingPdo $db;
    private VenTransferModel $model;

    protected function setUp(): void {
        $this->db = new SqliteLockingPdo('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->sqliteCreateFunction('NOW', fn() => '2026-10-08 12:00:00');
        $this->db->exec('CREATE TABLE user (id TEXT PRIMARY KEY, status INTEGER, is_deleted INTEGER)');
        $this->db->exec('CREATE TABLE ven_user (id INTEGER PRIMARY KEY, user_id TEXT, ven_name_sub_id INTEGER)');
        $this->db->exec('CREATE TABLE ven_schedule (id INTEGER PRIMARY KEY, user_id TEXT, ven_name_sub_id INTEGER, ven_date TEXT, status INTEGER)');
        $this->db->exec('CREATE TABLE ven_change (id INTEGER PRIMARY KEY AUTOINCREMENT, change_no TEXT, s1_id INTEGER, user1_id TEXT, user2_id TEXT, is_swap INTEGER, s2_id INTEGER, status INTEGER, created_at TEXT)');
        $this->db->exec('CREATE TABLE system_settings (id INTEGER, allow_swap INTEGER, advance_swap_days INTEGER, allow_retroactive_swap INTEGER)');
        $this->db->exec('INSERT INTO system_settings VALUES (1,1,3,0)');
        $this->db->exec("INSERT INTO user VALUES ('alice',10,0),('bob',10,0),('eve',10,0)");
        $this->db->exec("INSERT INTO ven_user VALUES (1,'alice',10),(2,'bob',10)");
        $this->db->exec("INSERT INTO ven_schedule VALUES (1,'alice',10,'2026-10-15',1),(2,'bob',10,'2026-10-16',1)");
        $this->model = new VenTransferModel($this->db, fn() => '2026-10-08');
    }

    public function test_other_user_cannot_transfer_someone_elses_schedule(): void {
        $result = $this->model->performTransfer('eve', 1, 'bob', 0, null);
        $this->assertSame(403, $result['code']);
        $this->assertSame('alice', $this->db->query('SELECT user_id FROM ven_schedule WHERE id = 1')->fetchColumn());
    }
    public function test_disabled_transfer_cannot_be_bypassed_by_api(): void {
        $this->db->exec('UPDATE system_settings SET allow_swap=0');
        $this->assertSame(403, $this->model->performTransfer('alice',1,'bob',0,null)['code']);
    }
    public function test_advance_day_rule_is_enforced_on_server(): void {
        $this->db->exec("UPDATE ven_schedule SET ven_date='2026-10-09' WHERE id=1");
        $this->assertSame(403, $this->model->performTransfer('alice',1,'bob',0,null)['code']);
    }
    public function test_retroactive_rule_is_enforced_on_server(): void {
        $this->db->exec("UPDATE ven_schedule SET ven_date='2026-10-07' WHERE id=1");
        $this->assertSame(403, $this->model->performTransfer('alice',1,'bob',0,null)['code']);
    }

    public function test_receiver_must_be_eligible(): void {
        $result = $this->model->performTransfer('alice', 1, 'eve', 0, null);
        $this->assertSame(400, $result['code']);
    }

    public function test_only_request_owner_can_cancel_pending_transfer(): void {
        $this->assertTrue($this->model->performTransfer('alice', 1, 'bob', 0, null)['success']);
        $this->assertLessThanOrEqual(20, strlen($this->db->query('SELECT change_no FROM ven_change WHERE id=1')->fetchColumn()));
        $this->assertSame(403, $this->model->cancelTransfer(1, 'eve')['code']);
        $this->assertSame('bob', $this->db->query('SELECT user_id FROM ven_schedule WHERE id = 1')->fetchColumn());
        $this->assertTrue($this->model->cancelTransfer(1, 'alice')['success']);
        $this->assertSame('alice', $this->db->query('SELECT user_id FROM ven_schedule WHERE id = 1')->fetchColumn());
    }

    public function test_approved_transfer_cannot_be_cancelled(): void {
        $this->assertTrue($this->model->performTransfer('alice', 1, 'bob', 0, null)['success']);
        $this->db->exec('UPDATE ven_change SET status = 1 WHERE id = 1');
        $this->assertSame(409, $this->model->cancelTransfer(1, 'alice')['code']);
    }

    public function test_swap_requires_receiver_to_own_other_schedule(): void {
        $result = $this->model->performTransfer('alice', 1, 'eve', 1, 2);
        $this->assertSame(403, $result['code']);
    }
}
