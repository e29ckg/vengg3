<?php
// backend/src/Controllers/VenApproveController.php

require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class VenApproveController {
    private $approveModel;
    private $db; // 🌟 เพิ่มตัวแปร db สำหรับใช้บันทึก Log

    // 🌟 รับค่า $db เข้ามาใน Constructor ด้วย
    public function __construct($approveModel, $db = null) {
        $this->approveModel = $approveModel;
        $this->db = $db;
    }

    // ฟังก์ชันดึงรายการ
    public function getList() {
        try {
            $results = $this->approveModel->getChangeRequests();
            echo json_encode($results);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server Error']);
        }
    }

    // ฟังก์ชันบังคับอัปเดตสถานะ
    public function forceUpdate($data) {
        $change_id = $data['change_id'] ?? null;
        $status = $data['status'] ?? 1;
        
        if (!$change_id) {
            http_response_code(400); 
            echo json_encode(['error' => 'ไม่พบรหัสการเปลี่ยนเวร']); 
            return;
        }

        // ส่งให้ Model จัดการและรับผลลัพธ์
        $result = $this->approveModel->forceUpdateStatus($change_id, $status);

        if ($result['success']) {
            
            // 🌟 บันทึก Log การอนุมัติใบเปลี่ยนเวร
            try {
                if ($this->db) {
                    $logModel = new LogModel($this->db);
                    
                    // ดึง ID ของผู้ใช้งานปัจจุบัน (ผู้อนุมัติ)
                    $adminId = AuthMiddleware::getUserIdFromToken($this->db);
                    
                    // แปลงตัวเลขสถานะเป็นข้อความให้ดูใน Log ง่ายๆ
                    $statusText = ($status == 1) ? 'อนุมัติ' : (($status == 2) ? 'ไม่อนุมัติ' : 'รออนุมัติ');

                    $logModel->addLog(
                        $adminId,               
                        'UPDATE',               // แอคชัน
                        'APPROVE',              // โมดูล: การอนุมัติเวร
                        "อัปเดตสถานะใบเปลี่ยนเวร (ID อ้างอิง: {$change_id}, อัปเดตเป็นสถานะ: {$statusText})", 
                        null,                   // ข้อมูลเก่า
                        ['change_id' => $change_id, 'status' => $status] // ข้อมูลใหม่
                    );
                }
            } catch (Exception $e) {
                // หากบันทึก Log พลาด จะไม่ทำให้ระบบหลักพัง
                error_log("VenApprove Log Failed: " . $e->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'อนุมัติการเปลี่ยนเวรเรียบร้อยแล้ว']);
        } else {
            http_response_code($result['code'] ?? 500);
            echo json_encode(['error' => $result['error']]);
        }
    }
}
?>