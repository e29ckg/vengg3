<?php
// ไฟล์: backend/tests/AuthMiddlewareTest.php

use PHPUnit\Framework\TestCase;

// โหลดไฟล์คลาสที่เราต้องการจะทดสอบเข้ามา
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

class AuthMiddlewareTest extends TestCase
{
    /**
     * ทดสอบเคสที่ 1: พนักงานทั่วไป (Role 1) ต้องเข้าถึงฟังก์ชัน Admin ไม่ได้
     */
    public function test_normal_user_is_denied_admin_access()
    {
        // 1. Arrange (เตรียมข้อมูล): จำลองข้อมูล Token ของพนักงานทั่วไป
        $mockTokenData = [
            'user_id' => 15,
            'role' => 1 // 1 = พนักงานทั่วไป
        ];

        // 2. Expect (คาดหวัง): บอก PHPUnit ว่าเราคาดหวังให้ระบบโยน Exception ออกมา 
        // พร้อมข้อความปฏิเสธ (เปลี่ยนข้อความให้ตรงกับที่คุณเขียนไว้ใน AuthMiddleware)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access Denied');

        // 3. Act (ลงมือกระทำ): เรียกใช้ฟังก์ชันตรวจสอบสิทธิ์ (ถ้าโค้ดถูกต้อง มันจะโยน Exception ตรงนี้)
        AuthMiddleware::checkAdmin($mockTokenData);
    }

    /**
     * ทดสอบเคสที่ 2: ผู้ดูแลระบบ (Role 9) ต้องสามารถผ่านด่านได้ปกติ
     */
    public function test_admin_user_is_allowed()
    {
        // 1. Arrange: จำลองข้อมูล Token ของแอดมิน
        $mockTokenData = [
            'user_id' => 1,
            'role' => 9 // 9 = ผู้ดูแลระบบ
        ];

        // 2. Act: เรียกใช้ฟังก์ชันตรวจสอบสิทธิ์
        // (สมมติว่าฟังก์ชัน checkAdmin ของคุณคืนค่า true เมื่อผ่านด่าน)
        $result = AuthMiddleware::checkAdmin($mockTokenData);

        // 3. Assert (ตรวจสอบผลลัพธ์): ยืนยันว่าต้องคืนค่ากลับมาเป็นจริงเสมอ
        $this->assertTrue($result, "แอดมินควรจะผ่านการตรวจสอบสิทธิ์ได้");
    }
}