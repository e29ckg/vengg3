<?php
class VenTransferController {
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
            
            // 🌟 1. นำเข้าและเรียกใช้งาน Google Calendar Service
            require_once __DIR__ . '/../Services/GoogleCalendarService.php';
            $googleCalendar = new GoogleCalendarService($this->connection);

            // 🌟 2. สั่งอัปเดตปฏิทินของวันที่ 1
            if (!empty($result['date1'])) {
                $googleCalendar->updateDay($result['date1']);
            }
            
            // 🌟 3. ถ้าเป็นการสลับเวร และวันที่ไม่ซ้ำกัน อัปเดตวันที่ 2 ด้วย
            if (!empty($result['date2']) && $result['date1'] != $result['date2']) {
                $googleCalendar->updateDay($result['date2']);
            }

            // ส่งผลลัพธ์กลับไปให้หน้าเว็บ
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

    public function cancel($data) {
        $change_id = $data['change_id'] ?? null;

        if (!$change_id) {
            http_response_code(400); 
            echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']); 
            return;
        }

        $result = $this->transferModel->cancelTransfer($change_id);

        if ($result['success']) {
            $changeReq = $result['data'];
            
            // 🌟 1. นำเข้าและเรียกใช้งาน Google Calendar Service
            require_once __DIR__ . '/../Services/GoogleCalendarService.php';
            $googleCalendar = new GoogleCalendarService($this->connection);

            // 🌟 2. อัปเดตปฏิทินของวันที่ 1
            if (!empty($changeReq['date1'])) {
                $googleCalendar->updateDay($changeReq['date1']);
            }
            
            // 🌟 3. อัปเดตปฏิทินของวันที่ 2 (ถ้าเป็นการสลับเวร และวันที่ไม่ซ้ำกัน)
            if ($changeReq['is_swap'] == 1 && !empty($changeReq['date2']) && $changeReq['date1'] != $changeReq['date2']) {
                $googleCalendar->updateDay($changeReq['date2']);
            }

            echo json_encode(['success' => true, 'message' => 'ยกเลิกการเปลี่ยนเวรและอัปเดตปฏิทินเรียบร้อยแล้ว']);
        } else {
            http_response_code($result['code']);
            echo json_encode(['error' => $result['error']]);
        }
    }
    
}