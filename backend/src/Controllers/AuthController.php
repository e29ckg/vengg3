<?php
// backend/src/Controllers/AuthController.php

require_once '../src/Models/User.php';

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
}
?>