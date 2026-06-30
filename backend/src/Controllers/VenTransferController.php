<?php
// backend/src/Controllers/VenTransferController.php

require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class VenTransferController {
    private $transferModel;
    private $connection; 

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
            
            // 🌟 1. นำเข้าและอัปเดต Google Calendar
            try {
                require_once __DIR__ . '/../Services/GoogleCalendarService.php';
                $googleCalendar = new GoogleCalendarService($this->connection);

                if (!empty($result['date1'])) {
                    $googleCalendar->updateDay($result['date1']);
                }
                
                if (!empty($result['date2']) && $result['date1'] != $result['date2']) {
                    $googleCalendar->updateDay($result['date2']);
                }
            } catch (Exception $e) {
                error_log("Google Calendar Update Failed (Perform): " . $e->getMessage());
            }

            // 🌟 2. บันทึก Log การสร้างคำขอเปลี่ยนเวร
            try {
                $logModel = new LogModel($this->connection);
                $changeNo = $result['change_no'] ?? 'ไม่ระบุ';
                $actionType = $is_swap ? "สลับเวร" : "ยกเวร"; // เช็คว่าเป็นยกให้หรือสลับ

                $logModel->addLog(
                    $currentUserId,               
                    'CREATE',               // แอคชัน: สร้างใหม่
                    'TRANSFER',             // โมดูล: การเปลี่ยนเวร
                    "สร้างคำขอ{$actionType} (ใบเปลี่ยนเลขที่: {$changeNo})", // รายละเอียด
                    null,                   // ข้อมูลเก่า (ไม่มีเพราะสร้างใหม่)
                    $data                   // ข้อมูลที่ส่งมาขอเปลี่ยน
                );
            } catch (Exception $e) {
                error_log("Log Insert Failed (Perform Transfer): " . $e->getMessage());
            }

            // ส่งผลลัพธ์กลับไปให้หน้าเว็บ
            echo json_encode([ 
                'success' => true, 
                'message' => 'บันทึกคำขอเปลี่ยนเวรสำเร็จ', 
                'change_no' => $result['change_no'] 
            ]);

        } else {
            http_response_code($result['code'] ?? 500);
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
            $changeReq = $result['data']; // ข้อมูลของใบเปลี่ยนเวรก่อนถูกยกเลิก
            
            // 🌟 1. อัปเดต Google Calendar ของวันที่มีการยกเลิก
            try {
                require_once __DIR__ . '/../Services/GoogleCalendarService.php';
                $googleCalendar = new GoogleCalendarService($this->connection);

                if (!empty($changeReq['date1'])) {
                    $googleCalendar->updateDay($changeReq['date1']);
                }
                
                if (isset($changeReq['is_swap']) && $changeReq['is_swap'] == 1 && !empty($changeReq['date2']) && $changeReq['date1'] != $changeReq['date2']) {
                    $googleCalendar->updateDay($changeReq['date2']);
                }
            } catch (Exception $e) {
                error_log("Google Calendar Update Failed (Cancel): " . $e->getMessage());
            }

            // 🌟 2. บันทึก Log การยกเลิกใบเปลี่ยนเวร
            try {
                // ดึง ID ของคนที่กดยกเลิก
                $currentUserId = AuthMiddleware::getUserIdFromToken($this->connection);
                $logModel = new LogModel($this->connection);
                $changeNo = $changeReq['change_no'] ?? $change_id; // ดึงเลขที่ใบเปลี่ยนมาแสดง

                $logModel->addLog(
                    $currentUserId,               
                    'CANCEL',               // แอคชัน: ยกเลิก
                    'TRANSFER',             // โมดูล: การเปลี่ยนเวร
                    "ยกเลิกคำขอเปลี่ยนเวร (ใบเปลี่ยนเลขที่: {$changeNo})", // รายละเอียด
                    $changeReq,             // ข้อมูลใบเปลี่ยนเวร (ก่อนถูกยกเลิก)
                    null                   
                );
            } catch (Exception $e) {
                error_log("Log Insert Failed (Cancel Transfer): " . $e->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'ยกเลิกการเปลี่ยนเวรและอัปเดตปฏิทินเรียบร้อยแล้ว']);
        } else {
            http_response_code($result['code'] ?? 500);
            echo json_encode(['error' => $result['error']]);
        }
    }
}
?>