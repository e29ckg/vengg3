<?php
require_once __DIR__ . '/../Services/TelegramService.php';

class VenCommandController {
    private $settingModel;
    private $connection;
    private $telegramFactory;

    public function __construct($settingModel, $connection, ?callable $telegramFactory = null) {
        $this->settingModel = $settingModel;
        $this->connection = $connection;
        $this->telegramFactory = $telegramFactory ?? static fn($db) => new TelegramService($db);
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
        if (!is_array($data) || !isset($data['id'], $data['status']) ||
            !(is_int($data['id']) || is_string($data['id'])) ||
            filter_var($data['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>2147483647]]) === false ||
            !in_array($data['status'], [0, 1, '0', '1'], true)) {
            http_response_code(400);
            echo json_encode(['error'=>'รหัสคำสั่งหรือสถานะไม่ถูกต้อง']);
            return;
        }
        $result = $this->settingModel->toggleVenCommandStatus($data['id'], $data['status']);

        if ($result) {
            $notificationSent = null;
            // ถ้าสถานะคือ 1 (ยืนยันตารางแล้ว) ให้ส่งแจ้งเตือน Telegram
            if ($data['status'] == 1) { 
                $msg = "📢 <b>ประกาศจากระบบจัดเวร</b>\n";
                $msg .= "ตารางเวรประจำเดือนได้รับการยืนยันเรียบร้อยแล้ว!\n";
                $msg .= "สมาชิกสามารถเข้าสู่ระบบเพื่อตรวจสอบ หรือส่งคำขอแลกเปลี่ยนเวรได้เลยครับ";
                
                // The status is already saved. Notification transport failures must not
                // turn a successful update into HTTP 500 and reset the browser's switch.
                try {
                    $telegram = ($this->telegramFactory)($this->connection);
                    $notificationSent = (bool)$telegram->sendMessage($msg, 'notify_confirmed');
                } catch (Throwable $error) {
                    error_log('Command confirmation notification failed: ' . get_class($error));
                    $notificationSent = false;
                }
            }
            echo json_encode(['success'=>true, 'notification_sent'=>$notificationSent]);
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
