<?php
// backend/src/Controllers/VenController.php

require_once '../src/Models/Ven.php';

class VenController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ฟังก์ชันรับ Request ดึงข้อมูลเวรทั้งหมด หรือตามเดือน
    public function getList($monthYear = null) {
        $ven = new Ven($this->db);
        $stmt = $ven->getVenList($monthYear);
        $rowCount = $stmt->rowCount();

        $ven_arr = array();

        if ($rowCount > 0) {
            $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'], // 🌟 เพิ่ม user_id เข้าไปในข้อมูลที่ส่งกลับ
                'title' => $row['title'],
                'date' => $row['date'],
                'backgroundColor' => $row['backgroundColor'],
                'ven_time' => $row['ven_time']
            ];
        }

        // ส่งข้อมูลออกไป
        echo json_encode($events);
        exit; // 🌟 เติมบรรทัดนี้! เพื่อสั่งให้ PHP หยุดทำงานทันที ป้องกันไม่ให้มี [] หรือขยะอื่นๆ ตามมาต่อท้าย
        }
        
        // ส่งผลลัพธ์กลับไปเป็น JSON
        http_response_code(200);
        echo json_encode($ven_arr);
    }

    // ฟังก์ชันรับ Request ดึงรายละเอียดเวร 1 รายการ
    public function getDetail($id) {
        if(empty($id)) {
            http_response_code(400);
            echo json_encode(["error" => "Incomplete parameters. Missing 'id'."]);
            return;
        }

        $ven = new Ven($this->db);
        $stmt = $ven->getVenDetail($id);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $history = $ven->getChangeHistory($id);
            // จัดรูปแบบ Data ให้ส่งกลับไปแบบคลีนๆ
            $ven_detail = array(
                "ven_id" => $row['ven_id'],
                "user_id" => $row['user_id'],
                
                "full_name" => $row['full_name'] ? $row['full_name'] : 'ไม่มีชื่อ',
                "profile_image" => $row['profile_image'] ? $row['profile_image'] : 'default_avatar.jpg',
                "ven_date" => $row['ven_date'],
                "com_status" => $row['com_status'],
                "ven_time" => $row['ven_time'],
                "duty_main" => $row['duty_main'],
                "duty_role" => $row['duty_role'],
                "sub_id" => $row['sub_id'],
                "price" => $row['price'],
                "command_num" => $row['command_num'] ? $row['command_num'] : 'ไม่มีคำสั่ง',
                "color" => $row['color'],
                "status" => $row['status'],
                "history" => $history,

                // 🌟 เพิ่ม 3 บรรทัดนี้ เพื่อส่งข้อมูลประวัติการเปลี่ยนเวรไปให้ Vue.js
                "change_id" => $row['change_id'] ?? null,
                "user1_name" => $row['user1_name'] ?? null,
                "user2_name" => $row['user2_name'] ?? null
            );

            http_response_code(200);
            echo json_encode($ven_detail);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Ven record not found."]);
        }
    }

    
}
?>