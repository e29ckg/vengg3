<?php
class GoogleCalendarService {
    private $connection;
    private $baseDir;

    // รับค่า Connection และ Base Directory มาจาก Controller
    public function __construct($connection, $baseDir = __DIR__) {
        $this->connection = $connection;
        $this->baseDir = $baseDir;
    }

    // 🌟 ฟังก์ชันตัวช่วยสำหรับอัปเดต Google Calendar เฉพาะวันที่กำหนด
    public function updateDay($date) {
        // 1. ตรวจสอบไฟล์ Credentials และ Calendar ID
        $keyFilePath = $this->baseDir . '/../Config/credentials.json';
        if (!file_exists($keyFilePath)) return false;

        $stmtConf = $this->connection->prepare("SELECT setting_value FROM google_service_settings WHERE setting_key = 'google_calendar_id'");
        $stmtConf->execute();
        $calId = $stmtConf->fetchColumn();
        if (!$calId) return false;

        // 2. ดึงข้อมูลตารางเวรล่าสุดของ "วันนั้น"
        $stmt = $this->connection->prepare("
            SELECT 
                vs.ven_date,
                vn.dn AS ven_time,
                vns.name AS sub_name,
                vn.name AS main_name,
                vs.google_event_id,
                CONCAT_WS(' ', CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, '')), p.last_name) AS user_name
            FROM ven_schedule vs
            LEFT JOIN ven_name_sub vns ON vs.ven_name_sub_id = vns.id
            LEFT JOIN ven_com vc ON vs.ven_com_id = vc.id
            LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id
            LEFT JOIN profile p ON vs.user_id = p.user_id 
            WHERE vs.ven_date = ?
            ORDER BY vn.srt ASC, vns.srt ASC;
        ");
        $stmt->execute([$date]);
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($shifts)) return false;

        // 3. หา google_event_id ของวันนั้น
        $googleEventId = null;
        foreach ($shifts as $sch) {
            if (!empty($sch['google_event_id'])) {
                $googleEventId = $sch['google_event_id'];
                break;
            }
        }
        
        // หากวันนั้นไม่เคยถูกซิงค์ขึ้น Google มาก่อน ก็ข้ามการอัปเดตไป
        if (!$googleEventId) return false; 

        // 4. สร้างข้อความ Description ขึ้นมาใหม่จากข้อมูลล่าสุด
        $groupedByDuty = [];
        foreach ($shifts as $sch) {
            $dutyName = $sch['main_name'];
            if (!isset($groupedByDuty[$dutyName])) { $groupedByDuty[$dutyName] = []; }
            $groupedByDuty[$dutyName][] = $sch;
        }

        $description = "เวรประจำวันที่ " . date('d/m/Y', strtotime($date)) . "\n";
        foreach ($groupedByDuty as $dutyName => $dutyShifts) {
            $description .= "---------------------\n" . "📌". $dutyName . "\n";
            foreach ($dutyShifts as $sch) {
                $uName = trim($sch['user_name']) ?: "(ยังไม่มีผู้ลงเวร)";
                $description .= "👨‍💼 " . $uName . "\n";
            }
            $description .= "\n";
        }

        // 5. เชื่อมต่อ Google API และสั่งอัปเดตข้อมูล
        require_once $this->baseDir . '/../../vendor/autoload.php';
        $client = new Google_Client();
        $client->setAuthConfig($keyFilePath);
        $client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $service = new Google_Service_Calendar($client);

        try {
            // ดึง Event เดิมขึ้นมา
            $event = $service->events->get($calId, $googleEventId);
            // แก้ไขแค่รายละเอียด (รายชื่อ)
            $event->setDescription($description);
            // ส่งกลับไปทับของเดิม
            $service->events->update($calId, $googleEventId, $event);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}