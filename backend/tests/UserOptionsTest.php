<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Models/SettingModel.php';

final class UserOptionsTest extends TestCase
{
    private function database(): PDO
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE system_settings (id INTEGER PRIMARY KEY, user_options TEXT)');
        return $db;
    }

    public function testFreshEmptyObjectSupportsAddingAndRemovingOptions(): void
    {
        $db = $this->database();
        $db->exec("INSERT INTO system_settings VALUES (1, '{}')");
        $model = new SettingModel($db);
        $options = $model->getUserOptions();
        foreach (['prefixes' => 'นาย', 'positions' => 'เจ้าหน้าที่', 'departments' => 'อำนวยการ'] as $key => $value) {
            self::assertFalse(in_array($value, $options[$key], true));
            $options[$key][] = $value;
        }
        self::assertTrue($model->saveUserOptions($options));
        self::assertSame($options, $model->getUserOptions());
        $options['prefixes'] = [];
        self::assertTrue($model->saveUserOptions($options));
        self::assertSame([], $model->getUserOptions()['prefixes']);
        self::assertSame(['เจ้าหน้าที่'], $model->getUserOptions()['positions']);
    }

    public function testMissingRowCanBeSaved(): void
    {
        $model = new SettingModel($this->database());
        $options = $model->getUserOptions();
        $options['departments'][] = 'การเงิน';
        self::assertTrue($model->saveUserOptions($options));
        self::assertSame($options, $model->getUserOptions());
    }

    public function testInvalidCollectionsDoNotDiscardValidOptions(): void
    {
        $db = $this->database();
        $stmt = $db->prepare('INSERT INTO system_settings VALUES (1, ?)');
        $stmt->execute([json_encode(['prefixes' => null, 'positions' => 'invalid', 'departments' => ['การเงิน', null, []]])]);
        self::assertSame(['prefixes' => [], 'positions' => [], 'departments' => ['การเงิน']], (new SettingModel($db))->getUserOptions());
        $db->exec("UPDATE system_settings SET user_options = 'invalid JSON'");
        self::assertSame(['prefixes' => [], 'positions' => [], 'departments' => []], (new SettingModel($db))->getUserOptions());
    }
}
