<?php
// backend/src/Controllers/LogController.php

require_once __DIR__ . '/../Models/LogModel.php';

class LogController {
    private $logModel;

    public function __construct($db) {
        $this->logModel = new LogModel($db);
    }

    public function getLogs() {
        try {
            // ดึงข้อมูล Log ทั้งหมด (จำกัด 500 รายการล่าสุด)
            $logs = $this->logModel->getAllLogs(500);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data" => $logs
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "ไม่สามารถดึงข้อมูลประวัติการทำงานได้"
            ]);
        }
    }
}
?>