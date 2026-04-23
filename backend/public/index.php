<?php
// backend/public/index.php

// 1. ตั้งค่า Header สำหรับ API และเปิดใช้งาน CORS
header("Access-Control-Allow-Origin: *"); // อนุญาตให้ทุกโดเมนเรียกใช้ API ได้ (ตอนขึ้น Production ค่อยแก้เป็นโดเมนจริง)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// จัดการกับ preflight request (OPTIONS) ของเบราว์เซอร์
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. โหลดไฟล์ที่จำเป็น
require_once '../config/database.php';

// 3. อ่านเส้นทาง (Route) ที่ Frontend เรียกมา
// สมมติว่าเรียกมาที่: http://localhost/vengg/backend/public/index.php?route=ven/list
$route = isset($_GET['route']) ? $_GET['route'] : '';

// 4. ทดสอบระบบ API พื้นฐาน
if ($route === 'test') {
    $db = new Database();
    $connection = $db->getConnection();
    
    if($connection) {
        echo json_encode([
            "status" => "success", 
            "message" => "Backend API is running and Database is connected!"
        ]);
    }
} else {
    // ถ้าเรียก Route ที่ไม่มีอยู่จริง
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found."]);
}
?>