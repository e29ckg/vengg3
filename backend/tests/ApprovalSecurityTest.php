<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Models/VenApproveModel.php';

class ApprovalLockingPdo extends PDO {
    public function prepare($query, $options = []): PDOStatement|false { return parent::prepare(str_replace(' FOR UPDATE','',$query),$options); }
}
final class ApprovalSecurityTest extends TestCase
{
    private function fixture(): array {
        $db = new ApprovalLockingPdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE ven_change (id INTEGER, s1_id INTEGER, s2_id INTEGER, user1_id TEXT, user2_id TEXT, is_swap INTEGER, status INTEGER)');
        $db->exec('CREATE TABLE ven_schedule (id INTEGER, user_id TEXT, status INTEGER)');
        $db->exec("INSERT INTO ven_change VALUES (1,1,NULL,'alice','bob',0,0)");
        $db->exec("INSERT INTO ven_schedule VALUES (1,'bob',2)");
        return [$db,new VenApproveModel($db)];
    }
    public function testApprovalAndReturningToPendingUseCorrectScheduleStatus(): void {
        [$db,$model]=$this->fixture();
        self::assertTrue($model->forceUpdateStatus(1,1)['success']);
        self::assertSame(1,(int)$db->query('SELECT status FROM ven_schedule')->fetchColumn());
        self::assertTrue($model->forceUpdateStatus(1,0)['success']);
        self::assertSame(2,(int)$db->query('SELECT status FROM ven_schedule')->fetchColumn());
    }
    public function testCancelledOrSupersededRequestCannotAlterSchedule(): void {
        [$db,$model]=$this->fixture();
        $db->exec('UPDATE ven_change SET status=2 WHERE id=1');
        self::assertSame(409,$model->forceUpdateStatus(1,1)['code']);
        $db->exec('UPDATE ven_change SET status=0 WHERE id=1');
        $db->exec("INSERT INTO ven_change VALUES (2,1,NULL,'bob','alice',0,0)");
        self::assertSame(409,$model->forceUpdateStatus(1,1)['code']);
        self::assertSame(2,(int)$db->query('SELECT status FROM ven_schedule')->fetchColumn());
    }
    public function testChangedOwnerCannotBeApproved(): void {
        [$db,$model]=$this->fixture();
        $db->exec("UPDATE ven_schedule SET user_id='eve'");
        self::assertSame(409,$model->forceUpdateStatus(1,1)['code']);
    }
}
