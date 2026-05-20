<?php
// backend/src/Controllers/LogController.php

require_once __DIR__ . '/../Models/LogModel.php';

class LogController {
    
    private $logModel;

    public function __construct($logModel) {
        $this->logModel = $logModel;
    }

    // ดึงประวัติทั้งหมดส่งให้แอดมินดู
    public function getLogs() {
        $logs = $this->logModel->getAllLogs();
        http_response_code(200);
        echo json_encode($logs);
    }
}
?>