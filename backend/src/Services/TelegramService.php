<?php

class TelegramService {
    private $db;
    private $settings = null;

    // รับ PDO Connection เข้ามาตอนเรียกใช้ Class
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->loadSettings();
    }

    // ดึงการตั้งค่าจาก Database
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM telegram_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $this->settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Telegram Settings Error: " . $e->getMessage());
        }
    }

    /**
     * ฟังก์ชันสำหรับส่งข้อความ
     * @param string $message ข้อความที่ต้องการส่ง (รองรับ HTML เช่น <b>...</b>)
     * @param string $notificationType ประเภทการแจ้งเตือนเพื่อเช็คสวิตช์ เปิด/ปิด (เช่น 'notify_confirmed')
     */
    public function sendMessage($message, $notificationType = null) {
        // 1. ตรวจสอบว่ามีการตั้งค่า Token และ Chat ID หรือยัง
        if (!$this->settings || empty($this->settings['bot_token']) || empty($this->settings['chat_id'])) {
            return false; // ยังไม่ได้ตั้งค่า
        }

        // 2. ตรวจสอบว่าแอดมินปิดสวิตช์การแจ้งเตือนประเภทนี้ไว้หรือไม่
        if ($notificationType && isset($this->settings[$notificationType])) {
            if ($this->settings[$notificationType] == 0) {
                return false; // สวิตช์ถูกปิดไว้ ไม่ต้องส่ง
            }
        }

        // 3. เตรียมส่ง API ไปที่ Telegram
        $url = "https://api.telegram.org/bot" . $this->settings['bot_token'] . "/sendMessage";
        
        $data = [
            'chat_id' => $this->settings['chat_id'],
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ป้องกันปัญหา SSL บน Localhost
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $httpCode != 200) {
            error_log("Telegram Send Error: " . $error . " - Response: " . $response);
            return false;
        }
        
        return true;
    }
}
?>