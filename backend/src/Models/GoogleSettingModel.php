<?php
class GoogleSettingModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. ดึงข้อมูลการตั้งค่า
    public function getConfig() {
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value FROM google_service_settings");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // 2. อัปเดตข้อมูล (แบบพิมพ์กรอกเอง)
    public function updateConfig($account, $calendarId) {
        try {
            $this->conn->beginTransaction();
            
            $stmt1 = $this->conn->prepare("UPDATE google_service_settings SET setting_value = ? WHERE setting_key = 'google_service_account'");
            $stmt1->execute([$account]);
            
            $stmt2 = $this->conn->prepare("UPDATE google_service_settings SET setting_value = ? WHERE setting_key = 'google_calendar_id'");
            $stmt2->execute([$calendarId]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // 3. อัปเดตเฉพาะ Service Account (ดึงจากไฟล์ JSON อัตโนมัติ)
    public function updateServiceAccount($email) {
        $stmt = $this->conn->prepare("UPDATE google_service_settings SET setting_value = ? WHERE setting_key = 'google_service_account'");
        return $stmt->execute([$email]);
    }
}