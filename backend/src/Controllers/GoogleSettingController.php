<?php
require_once __DIR__ . "/../Services/GoogleCredentialValidator.php";
class GoogleSettingController {
    private $googleModel;

    public function __construct($googleModel) {
        $this->googleModel = $googleModel;
    }

    // ดึงค่า Config ส่งให้ Vue.js
    public function getConfig() {
        $settings = $this->googleModel->getConfig();
        echo json_encode([
            'google_service_account' => $settings['google_service_account'] ?? '',
            'google_calendar_id' => $settings['google_calendar_id'] ?? ''
        ]);
    }

    // อัปเดตค่า Config
    public function updateConfig($data) {
        $account = $data['google_service_account'] ?? '';
        $calendarId = $data['google_calendar_id'] ?? '';
        
        if ($this->googleModel->updateConfig($account, $calendarId)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'ไม่สามารถอัปเดตข้อมูลได้']);
        }
    }

    // จัดการอัปโหลดไฟล์ credentials.json
    public function uploadCredentials($file, $baseDir) {
        $temporary = null;
        try {
            if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) ||
                strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') throw new RuntimeException('ไฟล์อัปโหลดไม่ถูกต้อง');
            $credentials = GoogleCredentialValidator::validate(file_get_contents($file['tmp_name']));
            $directory = $baseDir . '/../src/Config';
            if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('เขียนไฟล์ไม่ได้');
            $temporary = tempnam($directory, 'credential-');
            if (!$temporary || file_put_contents($temporary, json_encode($credentials), LOCK_EX) === false || !rename($temporary, $directory . '/credentials.json')) throw new RuntimeException('บันทึกไฟล์ไม่ได้');
            @chmod($directory . '/credentials.json', 0600);
            $this->googleModel->updateServiceAccount($credentials['client_email']);
            echo json_encode(['success' => true, 'client_email' => $credentials['client_email']]);
        } catch (RuntimeException $error) {
            http_response_code(400);
            echo json_encode(['error' => 'อัปโหลดไม่สำเร็จ ใช้ service-account JSON จาก Google ขนาดไม่เกิน 64 KB ที่มี private key ถูกต้อง']);
        } finally {
            if ($temporary && is_file($temporary)) unlink($temporary);
        }
    }
}
