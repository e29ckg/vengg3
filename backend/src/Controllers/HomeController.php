<?php
class HomeController {
    private $transferModel;
    private $connection; // รับ Connection มาเผื่อไว้ส่งให้ฟังก์ชัน updateGoogleCalendarDay

    public function __construct($transferModel, $connection) {
        $this->transferModel = $transferModel;
        $this->connection = $connection;
    }

    public function perform($currentUserId, $data) {
        $s1_id = $data['schedule_id'] ?? null;
        $user2_id = $data['new_user_id'] ?? null;
        $is_swap = isset($data['is_swap']) ? (int)$data['is_swap'] : 0;
        $s2_id = $data['s2_id'] ?? null;

        if (!$s1_id || !$user2_id) {
            http_response_code(400); 
            echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']); 
            return;
        }

        // ส่งให้ Model ทำงาน
        $result = $this->transferModel->performTransfer($currentUserId, $s1_id, $user2_id, $is_swap, $s2_id);

        // ตรวจสอบผลลัพธ์
        if ($result['success']) {
            
            // 🌟 เมื่อ DB ทำงานสำเร็จ สั่งอัปเดตปฏิทิน Google ทันที
            if (function_exists('updateGoogleCalendarDay') && !empty($result['date1'])) {
                updateGoogleCalendarDay($this->connection, $result['date1']);
                
                // ถ้าเป็นการสลับเวร และวันที่ไม่ซ้ำกัน อัปเดตวันที่ 2 ด้วย
                if (!empty($result['date2']) && $result['date1'] != $result['date2']) {
                    updateGoogleCalendarDay($this->connection, $result['date2']);
                }
            }

            echo json_encode([ 
                'success' => true, 
                'message' => 'บันทึกคำขอเปลี่ยนเวรสำเร็จ', 
                'change_no' => $result['change_no'] 
            ]);

        } else {
            http_response_code($result['code']);
            echo json_encode(['error' => $result['error']]);
        }
    }
}