<?php
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
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension === 'json') {
                $destDir = $baseDir . '/../src/Config';
                $destPath = $destDir . '/credentials.json';
                
                // สร้างโฟลเดอร์ Config ถ้ายังไม่มี
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // อ่านไฟล์ JSON เพื่อดึง Email อัตโนมัติ
                    $jsonContent = file_get_contents($destPath);
                    $credentials = json_decode($jsonContent, true);
                    $clientEmail = $credentials['client_email'] ?? '';

                    // ถ้าดึงอีเมลได้ ให้อัปเดตลงตารางให้เลย
                    if ($clientEmail) {
                        $this->googleModel->updateServiceAccount($clientEmail);
                    }

                    echo json_encode([
                        'success' => true, 
                        'client_email' => $clientEmail
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'บันทึกไฟล์ลงเซิร์ฟเวอร์ไม่สำเร็จ']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'อนุญาตให้อัปโหลดเฉพาะไฟล์ .json เท่านั้น']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ไม่พบไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลด']);
        }
    }
}