<?php
class VenCommandController {
    private $settingModel;
    private $connection;

    public function __construct($settingModel, $connection) {
        $this->settingModel = $settingModel;
        $this->connection = $connection;
    }

    public function getList() {
        echo json_encode($this->settingModel->getVenCommands());
    }

    public function create($data) {
        echo json_encode(["success" => $this->settingModel->createVenCommand($data)]);
    }

    public function update($data) {
        echo json_encode(["success" => $this->settingModel->updateVenCommand($data)]);
    }

    public function delete($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "Missing command ID"]);
            return;
        }
        echo json_encode(["success" => $this->settingModel->deleteVenCommand($id)]);
    }

    public function toggleStatus($data) {
        $result = $this->settingModel->toggleVenCommandStatus($data['id'], $data['status']);

        if ($result) {
            // ถ้าสถานะคือ 1 (ยืนยันตารางแล้ว) ให้ส่งแจ้งเตือน Telegram
            if ($data['status'] == 1) { 
                $telegram = new TelegramService($this->connection);
                
                $msg = "📢 <b>ประกาศจากระบบจัดเวร</b>\n";
                $msg .= "ตารางเวรประจำเดือนได้รับการยืนยันเรียบร้อยแล้ว!\n";
                $msg .= "สมาชิกสามารถเข้าสู่ระบบเพื่อตรวจสอบ หรือส่งคำขอแลกเปลี่ยนเวรได้เลยครับ";
                
                $telegram->sendMessage($msg, 'notify_confirmed'); 
            }
            echo json_encode(["success" => true]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
        }
    }

    public function updateStatus($data) {
        if ($this->settingModel->updateCommandStatus($data['id'], $data['status'])) {
            echo json_encode(["success" => true, "message" => "อัปเดตสถานะสำเร็จ"]);
        } else {
            http_response_code(500); 
            echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
        }
    }
}