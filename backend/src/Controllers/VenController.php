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
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // จัดรูปแบบ Data ให้เข้ากับ Frontend Calendar
                $ven_item = array(
                    "id" => $row['id'],
                    // นำชื่อ-นามสกุล มาต่อกันเป็น Title ให้ปฏิทิน
                    "title" => $row['first_name'] . ' ' . $row['last_name'],
                    "start" => $row['ven_date'], 
                    "color" => $row['color'], // สีพื้นหลังป้ายเวร
                    "status" => $row['status'],
                    "role_name" => $row['role_name'],
                    "extendedProps" => array(
                        "ven_time" => $row['ven_time']
                    )
                );
                array_push($ven_arr, $ven_item);
            }
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
            
            // จัดรูปแบบ Data ให้ส่งกลับไปแบบคลีนๆ
            $ven_detail = array(
                "ven_id" => $row['ven_id'],
                "user_id" => $row['user_id'],
                "full_name" => $row['fname'] . $row['first_name'] . ' ' . $row['last_name'],
                "profile_image" => $row['img'] ? $row['img'] : 'default_avatar.jpg',
                "ven_date" => $row['ven_date'],
                "ven_time" => $row['ven_time'],
                "duty_main" => $row['main_duty_name'],
                "duty_role" => $row['role_name'],
                "price" => $row['price'],
                "command_num" => $row['ven_com_num'] ? $row['ven_com_num'] : 'ไม่มีคำสั่ง',
                "color" => $row['color'],
                "status" => $row['status']
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