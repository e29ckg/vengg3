<?php
class TelegramController {
    private $connection;
    private $settingModel;

    public function __construct($connection, $settingModel) {
        $this->connection = $connection;
        $this->settingModel = $settingModel;
    }

    // 1. ฟังก์ชันดึงข้อมูลการตั้งค่า
    public function getSettings() {
        $settings = $this->settingModel->getTelegramSettings();
        
        if (!$settings) {
            $settings = [
                'bot_token' => '', 'chat_id' => '',
                'notify_confirmed' => true, 
                'notify_change_request' => true, 
                'notify_approval' => true,
                'notify_times' => []
            ];
        } else {
            $settings['notify_confirmed'] = (bool)$settings['notify_confirmed'];
            $settings['notify_change_request'] = (bool)$settings['notify_change_request'];
            $settings['notify_approval'] = (bool)$settings['notify_approval'];
            
            try {
                $stmtTime = $this->connection->prepare("SELECT send_time, notify_day, status FROM telegram_notify_times ORDER BY send_time ASC");
                $stmtTime->execute();
                $notifyTimes = $stmtTime->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($notifyTimes as &$time) {
                    $time['status'] = (bool)$time['status'];
                    $time['notify_day'] = (int)$time['notify_day'];
                }
                $settings['notify_times'] = $notifyTimes;
            } catch (PDOException $e) {
                $settings['notify_times'] = [];
            }
        }
        
        echo json_encode($settings);
    }

    // 2. ฟังก์ชันอัปเดตการตั้งค่า
    public function updateSettings() {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->settingModel->updateTelegramSettings($data);
        
        if ($result) {
            echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
        }
    }

    // 3. ฟังก์ชันทดสอบส่งข้อความ (ไม่ใช้ TelegramService เพื่อจะได้เทส Token สดๆ)
    public function testMessage() {
        $data = json_decode(file_get_contents("php://input"), true);
        $botToken = $data['bot_token'] ?? '';
        $chatId = $data['chat_id'] ?? '';
        
        if(empty($botToken) || empty($chatId)) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณากรอก Token และ Chat ID ก่อนทดสอบ"]);
            return;
        }

        $message = "✅ <b>ทดสอบระบบแจ้งเตือน</b>\nข้อความนี้ถูกส่งจาก \"ระบบจัดเวรนอกเวลาทำการ\" เพื่อทดสอบการเชื่อมต่อ Telegram ครับ";
        
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            echo json_encode(["success" => true, "message" => "ส่งข้อความสำเร็จ!"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ส่งไม่สำเร็จ กรุณาตรวจสอบ Token และ Chat ID"]);
        }
    }

    // 4. ฟังก์ชันส่งแจ้งเตือนเวรแบบ Manual
    public function manualNotify($telegramService) {
        $data = json_decode(file_get_contents("php://input"), true);
        $day_offset = isset($data['day_offset']) ? (int)$data['day_offset'] : 0;

        if ($day_offset === 1) {
            $target_date = date('Y-m-d', strtotime('+1 day'));
            $display_date = date('d/m/Y', strtotime('+1 day'));
            $day_text = "วันพรุ่งนี้";
        } else {
            $target_date = date('Y-m-d');
            $display_date = date('d/m/Y');
            $day_text = "วันนี้";
        }

        $sql = "SELECT vs.*, p.prefix_name, p.first_name as staff_name, p.last_name, vn.name as duty_name 
                FROM ven_schedule vs 
                LEFT JOIN profile p ON vs.user_id = p.user_id 
                LEFT JOIN ven_com vc On vc.id = vs.ven_com_id 
                LEFT JOIN ven_name_sub vns ON vns.id = vs.ven_name_sub_id 
                LEFT JOIN ven_name vn ON vn.id = vns.ven_name_id 
                WHERE DATE(vs.ven_date) = :target_date
                ORDER BY vn.srt ASC, vns.srt ASC;";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':target_date' => $target_date]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($schedules) > 0) {
            $msg = "📢 <b>แจ้งเตือนเวรปฏิบัติงาน ($day_text)</b>\n📅 วันที่: " . $display_date . "\n➖➖➖➖➖➖➖➖➖➖\n";
            
            $currentDuty = '';
            foreach ($schedules as $row) {
                if ($currentDuty != $row['duty_name']) {
                    $msg .= "📌 <b>" . $row['duty_name'] . "</b>\n";
                    $currentDuty = $row['duty_name'];
                }
                $msg .= "   👤 " . $row['prefix_name'] . $row['staff_name'] . " " . $row['last_name'] . "\n";
            }
            
            $msg .= "➖➖➖➖➖➖➖➖➖➖\n🙏 โปรดเตรียมความพร้อมในการปฏิบัติหน้าที่ครับ";

            $result = $telegramService->sendMessage($msg);
            
            if ($result) {
                echo json_encode(["success" => true, "message" => "ส่งแจ้งเตือนเวรของ{$day_text}เข้ากลุ่มสำเร็จ!"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ส่งไม่สำเร็จ โปรดตรวจสอบ Token และ Chat ID"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["error" => "ไม่มีผู้ปฏิบัติหน้าที่ในตารางเวรของ{$day_text}ครับ"]);
        }
    }
}