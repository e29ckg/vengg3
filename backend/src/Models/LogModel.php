<?php
// backend/src/Models/LogModel.php

class LogModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🌟 ฟังก์ชันสำหรับบันทึก Log ลงฐานข้อมูล
    public function addLog($userId, $action, $module, $description, $oldData = null, $newData = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // แปลง Array เป็น JSON ก่อนบันทึก
        $oldJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
        $newJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $this->conn->prepare("INSERT INTO system_logs (user_id, action, module, description, old_data, new_data, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $module, $description, $oldJson, $newJson, $ip]);
    }

    // 🌟 ฟังก์ชันสำหรับดึง Log ไปแสดงที่หน้าเว็บ (จำกัด 500 รายการล่าสุด ป้องกันเว็บอืด)
    public function getAllLogs() {
        $stmt = $this->conn->prepare("
            SELECT l.*, p.first_name, p.last_name 
            FROM system_logs l
            LEFT JOIN profile p ON l.user_id = p.user_id
            ORDER BY l.created_at DESC 
            LIMIT 500
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>