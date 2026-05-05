<?php
// backend/src/Controllers/AuthController.php

require_once '../src/Models/User.php';
require_once '../src/Middleware/AuthMiddleware.php';

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ฟังก์ชันรับข้อมูลล็อกอิน
    public function login() {
        // รับข้อมูล JSON จาก Frontend (เช่น Vue, Axios)
        $data = json_decode(file_get_contents("php://input"));

        // ตรวจสอบว่าส่ง username และ password มาหรือไม่
        if (!empty($data->username) && !empty($data->password)) {
            $userModel = new User($this->db);
            $result = $userModel->login($data->username, $data->password);

            if ($result['success']) {
            
 
                http_response_code(200);
                echo json_encode([
                    "message" => "Login successful.",
                    "user" => $result['user']
                ]);
            } else {
                http_response_code(401); // 401 Unauthorized
                echo json_encode(["error" => $result['message']]);
            }
        } else {
            http_response_code(400); // 400 Bad Request
            echo json_encode(["error" => "Incomplete data. Please provide username and password."]);
        }
    }

    // ฟังก์ชันดึงข้อมูลผู้ใช้ปัจจุบัน (ใช้ Token)
    public function getMe() {
        // ใช้ Middleware ตรวจสอบ Token และรับข้อมูลที่ถอดรหัสออกมา
        $userData = AuthMiddleware::checkToken($this->db);
        
        // ถ้าผ่านการตรวจสอบ Token จะได้ข้อมูลผู้ใช้กลับมาใน $userData
        if ($userData) {
            http_response_code(200);
            
            // ตรวจสอบว่า $userData เป็น Array หรือ Object แล้วดึงค่าให้ถูกต้อง
            if (is_array($userData)) {
                $user_id = $userData['id'] ?? null;
                $username = $userData['username'] ?? null;
                $role = $userData['role'] ?? null;
            } else {
                $user_id = $userData->id ?? null;
                $username = $userData->username ?? null;
                $role = $userData->role ?? null;
            }

            echo json_encode([
                "success" => true,
                "user_id" => $user_id,
                "username" => $username,
                "role" => $role
            ]);
        }
        exit;
    }
}
?>