<?php
// backend/src/config/database.php

class Database {
    private $host = "db"; 
    private $db_name = "vengg_db";
    private $username = "root";
    private $password = "root"; // รหัสตาม docker-compose
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // สร้างการเชื่อมต่อแบบ PDO
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            
            // ตั้งค่าให้แสดง Error หากมีข้อผิดพลาดใน SQL
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ตั้งค่าให้ดึงข้อมูลออกมาเป็นแบบ Associative Array เสมอ
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            echo json_encode(["error" => "Database connection error: " . $exception->getMessage()]);
            exit();
        }

        return $this->conn;
    }
}
?>
