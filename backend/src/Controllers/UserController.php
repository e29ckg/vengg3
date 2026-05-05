<?php
// backend/src/Controllers/UserController.php

require_once '../src/Models/User.php';

class UserController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listUsers() {
        $userModel = new User($this->db);
        $stmt = $userModel->getAllUsers();
        
        $users_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ประกอบร่างชื่อเต็มให้สวยงาม สำหรับแสดงในตารางหลัก
            $fullName = $row['prefix_name'] . $row['first_name'] . ' ' . $row['last_name'];
            if (trim($fullName) === '') {
                $fullName = '-';
            }

            array_push($users_arr, [
                "id" => $row['id'],
                "username" => $row['username'],
                "full_name" => $fullName,
                "role" => $row['role'],
                "status" => $row['status'],
                "prefix_name" => $row['prefix_name'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "position" => $row['position'],
                "srt" => $row['srt'],
                "department" => $row['department'],
                "phone" => $row['phone'],
                "bank_account" => $row['bank_account'],
                "bank_comment" => $row['bank_comment'],
                "st" => $row['st']
            ]);
        }

        http_response_code(200);
        echo json_encode($users_arr);
    }

    // รับข้อมูลสร้างผู้ใช้ใหม่
    public function createUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // ตรวจสอบว่าส่งข้อมูลสำคัญมาครบไหม
        if (empty($data['username']) || empty($data['password']) || empty($data['first_name'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน"]);
            return;
        }

        $userModel = new User($this->db);
        $result = $userModel->createUser($data);

        if ($result['success']) {
            http_response_code(201); // 201 Created
            echo json_encode(["message" => $result['message']]);
        } else {
            http_response_code(400); // 400 Bad Request
            echo json_encode(["error" => $result['message']]);
        }
    }

   // รับคำสั่งเปลี่ยนสถานะ
    public function changeStatus() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // เช็คว่ามีการส่ง ID และ Status ใหม่มาไหม
        if (empty($data['id']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }

        $userModel = new User($this->db);
        
        // 1. ทำการเปลี่ยนสถานะการเข้าใช้งาน
        if ($userModel->toggleStatus($data['id'], $data['status'])) {
            
            // 🌟 2. ถ้าสถานะถูกปรับเป็น 0 (ระงับ) ให้สั่งปรับ srt = 999 ด้วย
            if ($data['status'] == 0) {
                $userModel->setLowestSeniority($data['id']);
            }
            
            http_response_code(200);
            echo json_encode(["message" => "อัปเดตสถานะสำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "เกิดข้อผิดพลาด ไม่สามารถอัปเดตสถานะได้"]);
        }
    }

    // รับข้อมูลอัปเดตผู้ใช้งาน
    public function updateUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['first_name']) || empty($data['role'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน"]);
            return;
        }

        $userModel = new User($this->db);
        $result = $userModel->updateUser($data);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(["message" => $result['message']]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => $result['message']]);
        }
    }   

    public function deleteUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณาระบุ ID ของผู้ใช้ที่ต้องการลบ"]);
            return;
        }

        $userModel = new User($this->db);
        if ($userModel->deleteUser($data['id'])) {
            http_response_code(200);
            echo json_encode(["message" => "ลบผู้ใช้สำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "เกิดข้อผิดพลาด ไม่สามารถลบผู้ใช้ได้"]);
        }
    }   
}
?>