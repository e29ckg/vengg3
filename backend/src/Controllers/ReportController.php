<?php
// backend/src/Controllers/ReportController.php

require_once '../src/Models/Report.php';

class ReportController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ฟังก์ชันรับ Request ดึงข้อมูลเวรทั้งหมด หรือตามเดือน
    public function getCommonReport($monthYear) {
        $reportModel = new Report($this->db);
        $stmt = $reportModel->getVenCommands($monthYear);
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
        $reportModel = new Report($this->db);
        
        // เรียกใช้ Model ของคุณ
        $stmt = $reportModel->getSchedulesByCommandId($commandId);
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
                'dep' => $row['dep'],
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
}

    


   
?>