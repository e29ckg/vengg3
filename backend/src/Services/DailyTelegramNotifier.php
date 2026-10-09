<?php
declare(strict_types=1);

final class DailyTelegramNotifier
{
    public function __construct(private PDO $db, private TelegramService $telegram)
    {
    }

    public function sendTest(DateTimeImmutable $at): bool
    {
        return $this->telegram->sendMessage('✅ ทดสอบการแจ้งเตือนเวรจาก XAMPP (' . $at->format('d/m/Y H:i') . ' น.)');
    }

    public function run(DateTimeImmutable $at, bool $dryRun = false, bool $preview = false): array
    {
        $at = $at->setTimezone(new DateTimeZone('Asia/Bangkok'));
        $time = $at->format('H:i');
        $sendDate = $at->format('Y-m-d');
        $result = ['time' => $at->format('Y-m-d H:i'), 'matched' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0];

        $stmt = $this->db->prepare("SELECT DISTINCT TIME_FORMAT(send_time, '%H:%i') AS send_time, notify_day FROM telegram_notify_times WHERE status = 1 AND TIME_FORMAT(send_time, '%H:%i') = :send_time AND notify_day IN (0, 1) ORDER BY notify_day");
        $stmt->execute(['send_time' => $time]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $slot) {
            $result['matched']++;
            $notifyDay = (int)$slot['notify_day'];
            $target = $at->modify('+' . $notifyDay . ' day');
            $messages = self::buildMessages($target, $notifyDay, $this->getSchedules($target->format('Y-m-d')));
            if ($dryRun) {
                $result['preview'][] = ['notify_day' => $notifyDay, 'target_date' => $target->format('Y-m-d'), 'message_count' => count($messages), 'messages' => $preview ? $messages : []];
                continue;
            }
            if (!$messages) {
                $result['skipped']++;
                continue;
            }

            // The unique key is the claim. An uncertain network result is never retried automatically.
            $claim = $this->db->prepare("INSERT IGNORE INTO telegram_delivery_log (send_date, send_time, notify_day, target_date, status) VALUES (:send_date, :send_time, :notify_day, :target_date, 'started')");
            $claim->execute(['send_date' => $sendDate, 'send_time' => $time . ':00', 'notify_day' => $notifyDay, 'target_date' => $target->format('Y-m-d')]);
            if ($claim->rowCount() !== 1) {
                $result['skipped']++;
                continue;
            }

            $sentCount = 0;
            foreach ($messages as $message) {
                if (!$this->telegram->sendMessage($message)) {
                    break;
                }
                $sentCount++;
            }
            $success = $sentCount === count($messages);
            $update = $this->db->prepare('UPDATE telegram_delivery_log SET status = :status, message_count = :message_count, finished_at = NOW() WHERE send_date = :send_date AND send_time = :send_time AND notify_day = :notify_day');
            $update->execute(['status' => $success ? 'sent' : 'failed', 'message_count' => $sentCount, 'send_date' => $sendDate, 'send_time' => $time . ':00', 'notify_day' => $notifyDay]);
            $result[$success ? 'sent' : 'failed']++;
        }
        return $result;
    }

    private function getSchedules(string $date): array
    {
        $stmt = $this->db->prepare("SELECT COALESCE(vn.name, 'ไม่ระบุหน้าที่') AS duty_name, COALESCE(p.prefix_name, '') AS prefix_name, COALESCE(p.first_name, '') AS staff_name, COALESCE(p.last_name, '') AS last_name FROM ven_schedule vs JOIN ven_com vc ON vc.id = vs.ven_com_id AND vc.status = '1' LEFT JOIN profile p ON p.user_id = vs.user_id LEFT JOIN ven_name_sub vns ON vns.id = vs.ven_name_sub_id LEFT JOIN ven_name vn ON vn.id = vns.ven_name_id WHERE vs.ven_date = :target_date ORDER BY vn.srt, vns.srt, vs.id");
        $stmt->execute(['target_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buildMessages(DateTimeImmutable $target, int $notifyDay, array $schedules): array
    {
        if (!$schedules) {
            return [];
        }
        $title = $notifyDay === 1 ? 'วันพรุ่งนี้' : 'วันนี้';
        $header = "📢 <b>แจ้งเตือนรายชื่อผู้ปฏิบัติหน้าที่เวร{$title}</b>\n📅 วันที่: " . $target->format('d/m/Y') . "\n";
        $messages = [];
        $message = $header;
        $currentDuty = null;
        foreach ($schedules as $row) {
            $duty = (string)$row['duty_name'];
            $name = trim((string)$row['prefix_name'] . (string)$row['staff_name'] . ' ' . (string)$row['last_name']);
            $line = ($duty !== $currentDuty ? '📌 <b>' . self::escape($duty) . "</b>\n" : '') . '   👤 ' . self::escape($name !== '' ? $name : 'ไม่ระบุชื่อ') . "\n";
            if (mb_strlen($message . $line, 'UTF-8') > 3500 && $message !== $header) {
                $messages[] = $message;
                $message = $header;
                $currentDuty = null;
                $line = '📌 <b>' . self::escape($duty) . "</b>\n" . '   👤 ' . self::escape($name !== '' ? $name : 'ไม่ระบุชื่อ') . "\n";
            }
            $message .= $line;
            $currentDuty = $duty;
        }
        $messages[] = $message;
        return $messages;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars(mb_substr($value, 0, 200, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
