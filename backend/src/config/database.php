<?php
// backend/src/config/database.php

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // สร้างการเชื่อมต่อแบบ PDO
            $localConfigPath = __DIR__ . '/database.local.php';
            $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
            if (!is_array($localConfig)) throw new RuntimeException('Invalid local database configuration');
            $host = getenv('DB_HOST') ?: ($localConfig['DB_HOST'] ?? 'localhost');
            $name = getenv('DB_NAME') ?: ($localConfig['DB_NAME'] ?? 'vengg_db');
            $port = getenv('DB_PORT') ?: ($localConfig['DB_PORT'] ?? '3306');
            $user = getenv('DB_USER') ?: ($localConfig['DB_USER'] ?? '');
            $password = getenv('DB_PASS') ?: ($localConfig['DB_PASS'] ?? '');
            if (!is_string($host) || !preg_match('/\A[A-Za-z0-9_.:-]+\z/', $host) || !is_string($name) || !preg_match('/\A[A-Za-z0-9_]{1,64}\z/', $name) || !is_string($user) || $user === '' || !is_string($password) || $password === '' || !ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) throw new RuntimeException('Incomplete database configuration');
            $this->conn = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $password, [PDO::ATTR_TIMEOUT => 5]);
            
            // ตั้งค่าให้แสดง Error หากมีข้อผิดพลาดใน SQL
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ตั้งค่าให้ดึงข้อมูลออกมาเป็นแบบ Associative Array เสมอ
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(Throwable $exception) {
            error_log('Database connection unavailable: ' . get_class($exception));
            http_response_code(503);
            echo json_encode(["error" => "Database connection unavailable"]);
            exit();
        }

        return $this->conn;
    }
}
?>
