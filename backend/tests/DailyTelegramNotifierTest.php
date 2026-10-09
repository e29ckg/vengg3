<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Services/DailyTelegramNotifier.php';

final class DailyTelegramNotifierTest extends TestCase
{
    public function testEscapesNamesAndDutyForTelegramHtml(): void
    {
        $messages = DailyTelegramNotifier::buildMessages(
            new DateTimeImmutable('2026-10-09', new DateTimeZone('Asia/Bangkok')),
            0,
            [['duty_name' => '<เวร & A>', 'prefix_name' => '', 'staff_name' => 'A < B', 'last_name' => '& C']]
        );
        self::assertCount(1, $messages);
        self::assertStringContainsString('&lt;เวร &amp; A&gt;', $messages[0]);
        self::assertStringContainsString('A &lt; B &amp; C', $messages[0]);
        self::assertStringNotContainsString('A < B', $messages[0]);
    }

    public function testLongScheduleIsSplitIntoTelegramSizedMessages(): void
    {
        $rows = array_fill(0, 120, [
            'duty_name' => 'เวรกลางวัน',
            'prefix_name' => '',
            'staff_name' => str_repeat('ก', 80),
            'last_name' => 'ทดสอบ',
        ]);
        $messages = DailyTelegramNotifier::buildMessages(new DateTimeImmutable('2026-10-09'), 1, $rows);
        self::assertGreaterThan(1, count($messages));
        foreach ($messages as $message) {
            self::assertLessThanOrEqual(3500, mb_strlen($message, 'UTF-8'));
            self::assertStringContainsString('วันพรุ่งนี้', $message);
        }
    }
}
