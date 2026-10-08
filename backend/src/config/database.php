<?php
// backend/src/config/database.php

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // สร้างการเชื่อมต่อแบบ PDO
            $host = getenv('DB_HOST') ?: 'localhost';
            $name = getenv('DB_NAME') ?: 'vengg_db';
            $user = getenv('DB_USER') ?: '';
            $password = getenv('DB_PASS') ?: '';
            $this->conn = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $password);
            
            // ตั้งค่าให้แสดง Error หากมีข้อผิดพลาดใน SQL
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ตั้งค่าให้ดึงข้อมูลออกมาเป็นแบบ Associative Array เสมอ
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            error_log('Database connection error: ' . $exception->getMessage());
            http_response_code(503);
            echo json_encode(["error" => "Database connection unavailable"]);
            exit();
        }

        return $this->conn;
    }
}
?>
