<?php
// backend/src/Models/LogModel.php

class LogModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ฟังก์ชันดึงประวัติ (ที่เราทำไว้แล้ว)
    public function getLogs() {
        $query = "SELECT l.*, p.first_name, p.last_name 
                  FROM system_logs l 
                  LEFT JOIN profile p ON l.user_id = p.user_id 
                  ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🌟 ดึงข้อมูลประวัติการทำรายการทั้งหมด (เรียงจากล่าสุดไปเก่าสุด)
    public function getAllLogs($limit = 500) {
        try {
            // ดึงข้อมูล Log และ JOIN กับตาราง profile เพื่อเอาชื่อคนทำรายการมาแสดง
            $sql = "
                SELECT 
                    l.id, 
                    l.user_id, 
                    l.action, 
                    l.module, 
                    l.description, 
                    l.created_at,
                    CONCAT_WS(' ', p.prefix_name, p.first_name, p.last_name) AS full_name
                FROM system_logs l
                LEFT JOIN profile p ON l.user_id = p.user_id
                ORDER BY l.created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($sql);
            // ป้องกันข้อมูลเยอะเกินไปจนเว็บค้าง โดยจำกัดไว้ที่ 500 รายการล่าสุด (ปรับเพิ่มลดได้)
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Logs Error: " . $e->getMessage());
            return [];
        }
    }

    // 🌟 ฟังก์ชันสำหรับบันทึก Log ลงฐานข้อมูล
    public function addLog($userId, $action, $module, $description, $oldData = null, $newData = null) {
        $query = "INSERT INTO system_logs (user_id, action, module, description, old_data, new_data) 
                  VALUES (:user_id, :action, :module, :description, :old_data, :new_data)";
        
        $stmt = $this->conn->prepare($query);

        // แปลง Array เป็น JSON ก่อนบันทึก (รองรับภาษาไทยไม่ให้เป็นตัวยึกยือ)
        $oldDataJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
        $newDataJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':module', $module);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':old_data', $oldDataJson);
        $stmt->bindParam(':new_data', $newDataJson);

        return $stmt->execute();
    }

    
}
?>