<?php
// backend/src/Controllers/ReportController.php

class ReportController {
    private $reportModel;

    // รับ Model เข้ามาใช้งาน
    public function __construct($reportModel) {
        $this->reportModel = $reportModel;
    }

    // ฟังก์ชันรับ Request ดึงข้อมูลเวรทั้งหมด หรือตามเดือน
    public function getCommonReport($monthYear) {
        $stmt = $this->reportModel->getVenCommands($monthYear);
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $commands = [];
        }
       
        http_response_code(200);
        echo json_encode($commands);
    }

 // ฟังก์ชันรับ Request ดึงรายการผู้อยู่เวรตาม ID คำสั่ง
    public function getScheduleDetails($commandId) {
        // เรียกใช้ Model ของคุณ
        $stmt = $this->reportModel->getSchedulesByCommandId($commandId);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ตัวแปรสำหรับเก็บข้อมูลที่จัดกลุ่มแล้ว
        $groupedData = [];

        foreach ($results as $row) {
            $date = $row['ven_date'];
            
            // ถ้าวันที่นี้ยังไม่มีใน Array ให้สร้างขึ้นมาใหม่
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [
                    'ven_date' => $date,
                    'staff_list' => [] // สร้าง Array เปล่ารอเก็บรายชื่อคนในวันนั้น
                ];
            }

            // นำรายชื่อคน ยัดใส่เข้าไปในวันที่นั้นๆ
            $groupedData[$date]['staff_list'][] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'position' => $row['position'],
                'ven_name' => $row['ven_name'],
                'sub_name' => $row['sub_name'],
                'status' => isset($row['status']) ? $row['status'] : 'ปกติ'
            ];
        }

        // แปลง Array ให้เป็นรูปแบบ Index ปกติ (ลบ Key ที่เป็นวันที่ออก ให้เหลือแต่โครงสร้าง Array)
        $finalResponse = array_values($groupedData);

        http_response_code(200);
        echo json_encode($finalResponse);
    }

    public function getPersonalSchedule() {
        // รับค่าพารามิเตอร์จาก GET
        // ✨ แก้ไข: รับค่า user_id เป็นข้อความ (String) ไม่ใส่ (int) เพื่อรองรับ varchar(36)
        $userId = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        // ตรวจสอบความถูกต้องของข้อมูลเบื้องต้น
        if (empty($userId) || $month === 0 || $year === 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
            return;
        }

        // เรียกใช้ Model
        $schedules = $this->reportModel->getPersonalSchedule($userId, $month, $year);

        // ส่งข้อมูลกลับเป็น JSON
        http_response_code(200);
        echo json_encode($schedules);
    }
}

    


   
?>