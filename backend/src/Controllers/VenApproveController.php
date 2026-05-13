<?php
class VenApproveController {
    private $approveModel;

    public function __construct($approveModel) {
        $this->approveModel = $approveModel;
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
            echo json_encode(['success' => true, 'message' => 'อนุมัติการเปลี่ยนเวรเรียบร้อยแล้ว']);
        } else {
            http_response_code($result['code']);
            echo json_encode(['error' => $result['error']]);
        }
    }
}