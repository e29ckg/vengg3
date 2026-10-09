<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Models/VenTimeModel.php';

final class VenTimeModelTest extends TestCase
{
    private PDO $db;

    private function model(): VenTimeModel
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('CREATE TABLE ven_time (id INTEGER PRIMARY KEY AUTOINCREMENT, name_th TEXT, time_period TEXT, srt INTEGER)');
        $this->db->exec('CREATE TABLE ven_name (id INTEGER PRIMARY KEY AUTOINCREMENT, dn TEXT)');
        return new VenTimeModel($this->db);
    }

    public function testCreateEditAndDeleteUnusedTime(): void
    {
        $model = $this->model();
        $id = $model->save(['name_th' => 'Extra', 'time_period' => '18:00-22:00', 'srt' => 3]);
        self::assertSame('18.00-22.00', $model->list()[0]['time_period']);
        self::assertSame(0, $model->list()[0]['used_count']);

        $model->save(['id' => $id, 'name_th' => 'Evening', 'time_period' => '18.00-22.00', 'srt' => 1]);
        self::assertSame('Evening', $model->list()[0]['name_th']);
        self::assertSame(1, (int)$model->list()[0]['srt']);

        $model->delete($id);
        self::assertSame([], $model->list());
    }

    public function testUsedTimeOnlyAllowsReordering(): void
    {
        $model = $this->model();
        $id = $model->save(['name_th' => 'Day', 'time_period' => '08.30-16.30', 'srt' => 1]);
        $this->db->exec("INSERT INTO ven_name (dn) VALUES ('Day(08.30-16.30)')");
        self::assertSame(1, $model->list()[0]['used_count']);

        $model->save(['id' => $id, 'name_th' => 'Day', 'time_period' => '08.30-16.30', 'srt' => 2]);
        self::assertSame(2, (int)$model->list()[0]['srt']);
        try {
            $model->save(['id' => $id, 'name_th' => 'Other', 'time_period' => '08.30-16.30', 'srt' => 2]);
            self::fail('Changing a used label should be rejected');
        } catch (DomainException $error) {
            self::assertCount(1, $model->list());
        }
        $this->expectException(DomainException::class);
        $model->delete($id);
    }

    public function testRejectsInvalidAndDuplicateTimes(): void
    {
        $model = $this->model();
        $model->save(['name_th' => 'Day', 'time_period' => '08.30-16.30', 'srt' => 1]);
        try {
            $model->save(['name_th' => 'Day', 'time_period' => '08.30-16.30', 'srt' => 2]);
            self::fail('Duplicate labels should be rejected');
        } catch (DomainException $error) {
            self::assertCount(1, $model->list());
        }
        $this->expectException(InvalidArgumentException::class);
        $model->save(['name_th' => 'Bad', 'time_period' => '25.30-26.30', 'srt' => 1]);
    }
}
