<?php
// backend/src/Middleware/AuthMiddleware.php

require_once '../src/Models/User.php';

class AuthMiddleware {
    
    // ฟังก์ชันนี้จะถูกเรียกใช้ก่อนเข้าถึง API ที่สำคัญ
    public static function checkToken($db) {
        // 1. พยายามดึง Header ที่ชื่อว่า 'Authorization' จาก Request
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { 
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        // 2. ถ้าไม่มี Header ส่งมาเลย (ไม่มีบัตรมาแสดง)
        if (empty($headers)) {
            http_response_code(401); // 401 Unauthorized
            echo json_encode(["error" => "Access denied. No token provided."]);
            exit(); // หยุดการทำงานทันที (เตะออก)
        }

        // 3. ตรวจสอบรูปแบบว่าต้องขึ้นต้นด้วยคำว่า 'Bearer '
        $token = null;
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
        }

        if (empty($token)) {
            http_response_code(401);
            echo json_encode(["error" => "Access denied. Invalid token format."]);
            exit();
        }

        // 4. เอา Token ไปเช็คในฐานข้อมูล
        $userModel = new User($db);
        $userData = $userModel->validateToken($token);

        if (!$userData) {
            http_response_code(401);
            echo json_encode(["error" => "Access denied. Invalid or expired token."]);
            exit();
        }

        // ถ้าผ่านทุกด่าน คืนค่าข้อมูลผู้ใช้กลับไป (เผื่อเอาไปเช็คสิทธิ์ Role ต่อ)
        return $userData;
    }

    // 🔒 ยามระดับ VIP (เช็คว่าเป็น Admin เท่านั้น)
    public static function checkAdmin($db) {
        // 1. ให้ยามปกติเช็ค Token ก่อน
        $userData = self::checkToken($db);

        // 2. เช็คว่ามีสิทธิ์เป็น Admin (role = 9) หรือไม่
        if ($userData['role'] != 9) {
            http_response_code(403); // 403 Forbidden
            echo json_encode(["error" => "Access denied. Admin role required."]);
            exit();
        }

        return $userData;
    }
}
?>