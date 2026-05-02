<?php
// 🌟 1. กำหนด Timezone ให้ตรงกับประเทศไทย (สำคัญมากสำหรับ Cron Job)
date_default_timezone_set('Asia/Bangkok');

// 🌟 2. เรียกใช้ไฟล์เชื่อมต่อ DB และ Telegram Service (ปรับ Path ให้ตรงกับโปรเจกต์ของคุณ)
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../src/Services/TelegramService.php';

$db = new Database();
$conn = $db->getConnection();

// 🌟 3. ดึงเวลาปัจจุบัน (รูปแบบ ชั่วโมง:นาที เช่น 06:00)
$now = date('H:i');

// 🌟 4. ตรวจสอบว่าในฐานข้อมูลมีการตั้งเวลาตรงกับ "ตอนนี้" และ "เปิดใช้งานอยู่" หรือไม่
$stmt = $conn->prepare("SELECT * FROM telegram_notify_times WHERE status = 1 AND DATE_FORMAT(send_time, '%H:%i') = :now");
$stmt->execute([':now' => $now]);
$matchTime = $stmt->fetch(PDO::FETCH_ASSOC);

// ถ้าไม่มีเวลาตรงกับตอนนี้เลย ให้หยุดทำงานทันทีโดยไม่แสดง Error (เพราะ Cron จะรันทุกนาที)
if (!$matchTime) {
    exit; 
}

// 🌟 5. ตรวจสอบว่าแอดมินกรอก Token และ Chat ID หรือยัง
$stmt = $conn->prepare("SELECT * FROM telegram_settings WHERE id = 1 LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings || empty($settings['bot_token']) || empty($settings['chat_id'])) {
    exit("Telegram settings not configured.");
}

// 🌟 6. ดึงข้อมูลเวรของ "วันนี้"
$today = date('Y-m-d');

// (หมายเหตุ: ปรับชื่อตาราง ven_schedule, profile, ven_type ให้ตรงกับของคุณนะครับ)
$sql = "SELECT vs.*, p.first_name as staff_name, vt.name as duty_name 
        FROM ven_schedule vs
        LEFT JOIN profile p ON vs.user_id = p.user_id
        LEFT JOIN ven_type vt ON vs.ven_type_id = vt.id
        WHERE vs.ven_date = :today
        ORDER BY vt.id ASC"; // เรียงตามลำดับของหน้าที่ 

$stmt = $conn->prepare($sql);
$stmt->execute([':today' => $today]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🌟 7. จัดรูปแบบข้อความแจ้งเตือนและส่งเข้ากลุ่ม
if (count($schedules) > 0) {
    
    $msg = "📢 <b>แจ้งเตือนรายชื่อผู้ปฏิบัติหน้าที่เวรประจำวัน</b>\n";
    $msg .= "📅 วันที่: " . date('d/m/Y') . "\n";
    $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
    
    $currentDuty = '';
    foreach ($schedules as $row) {
        // จัดกลุ่มรายชื่อตามหน้าที่ให้อ่านง่าย
        if ($currentDuty != $row['duty_name']) {
            $msg .= "📌 <b>" . $row['duty_name'] . "</b>\n";
            $currentDuty = $row['duty_name'];
        }
        $msg .= "   👤 " . $row['staff_name'] . "\n";
    }
    
    $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
    $msg .= "🙏 ขอขอบคุณเจ้าหน้าที่ทุกท่านที่ปฏิบัติหน้าที่ครับ";

    // เรียกใช้ Service เพื่อส่งข้อความ
    $telegram = new TelegramService($conn);
    // ส่งข้อความโดยตรง ไม่ต้องเช็คเงื่อนไข notify_confirmed แล้ว เพราะเป็นรอบประจำวัน
    $telegram->sendMessage($msg);
    
    echo "✅ Sent daily schedule to Telegram at " . $now;

} else {
    echo "⚠️ No schedule for today. Time triggered: " . $now;
}
?>