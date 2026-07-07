<?php
// backend/src/Controllers/SettingController.php

class SettingController {
    private $settingModel;
    private $db; // 🌟 เพิ่มตัวแปร $db เพื่อใช้งานกับการบันทึก Log และ Model ภายใน

    // 🌟 เพิ่มรับพารามิเตอร์ $db เข้ามาใน Constructor ด้วย
    public function __construct($settingModel, $db = null) {
        $this->settingModel = $settingModel;
        $this->db = $db; 
    }

    // 🌟 [เพิ่มใหม่] ฟังก์ชันตัวช่วยสำหรับบันทึก Log เพื่อลดความซ้ำซ้อนของโค้ด
    private function addSystemLog($action, $module, $description, $oldData = null, $newData = null) {
        try {
            if (!$this->db) return; // ป้องกัน Error หากไม่มีการส่ง $db เข้ามา

            require_once __DIR__ . '/../Models/LogModel.php';
            require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
            
            $logModel = new LogModel($this->db);
            // ดึง ID ของคนที่กำลังทำรายการ
            $userId = AuthMiddleware::getUserIdFromToken($this->db);
            
            $logModel->addLog($userId, $action, $module, $description, $oldData, $newData);
        } catch (Exception $e) {
            error_log("Setting Log Failed: " . $e->getMessage());
        }
    }

    // ฟังก์ชันสำหรับดึงข้อมูลการตั้งค่า (GET)
    public function getSettings() {
        $settings = $this->settingModel->getSystemSettings();
        
        // ถ้าไม่มีข้อมูลใน Database ให้ใช้ค่า Default
        if (!$settings) {
            $settings = [
                'system_name' => 'ระบบบริหารจัดการเวรนอกเวลาทำการ', 
                'allow_swap' => 1, 
                'advance_swap_days' => 3, 
                'allow_retroactive_swap' => 0,
                'check_24h_consecutive' => 1,
                'compact_schedule_view' => 1,
                'maintenance_mode' => 0
            ];
        }        
        echo json_encode($settings);
    }

    // ฟังก์ชันสำหรับอัปเดตการตั้งค่า (POST)
    public function updateSettings() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // ส่งข้อมูลไปให้ Model อัปเดตลงฐานข้อมูล
        $result = $this->settingModel->updateSystemSettings($data);
        
        if ($result) {
            // 🌟 บันทึก Log
            $this->addSystemLog('UPDATE', 'SETTING', 'อัปเดตการตั้งค่าระบบส่วนกลาง', null, $data);
            echo json_encode(["success" => true, "message" => "อัปเดตการตั้งค่าระบบสำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
        }
    }
    
    public function updateToggle() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // ตรวจสอบความปลอดภัยว่ามีการส่ง key และ value มาครบถ้วน
        if (isset($data['setting_key']) && isset($data['setting_value'])) {
            
            // เรียกใช้ Model เพื่ออัปเดตข้อมูล
            if ($this->settingModel->updateSystemSetting($data['setting_key'], $data['setting_value'])) {
                // 🌟 บันทึก Log
                $this->addSystemLog('UPDATE', 'SETTING', "อัปเดตสถานะ Toggle (Key: {$data['setting_key']}, Value: {$data['setting_value']})");
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update"]);
            }

        } else {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
        }
    }

    // ดึงข้อมูลตั้งค่าหน่วยงาน
    public function getAgencySettings() {
        $settings = $this->settingModel->getAgencySettings();
        
        // คืนค่าเป็น JSON (ถ้าไม่มีข้อมูลให้คืนเป็น Array ว่างหรือ Default)
        echo json_encode($settings ? $settings : []);
    }

    // อัปเดตข้อมูลตั้งค่าหน่วยงาน
    public function updateAgencySettings() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($this->settingModel->updateAgencySettings($data)) {
            // 🌟 บันทึก Log
            $this->addSystemLog('UPDATE', 'SETTING', 'อัปเดตข้อมูลหน่วยงาน/ชื่อศาล', null, $data);
            echo json_encode(["success" => true, "message" => "บันทึกข้อมูลสำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลตั้งค่าหน่วยงานได้"]);
        }
    }

    public function handleRequest($action, $table) {
        $settingModel = new SettingModel($this->db);
        $data = json_decode(file_get_contents("php://input"), true);

        switch ($action) {
            case 'list':
                echo json_encode($settingModel->getAll($table));
                break;
            case 'ven_full':
                echo json_encode($settingModel->getVenFullData());
                break;
            case 'create':
                if ($settingModel->create($table, $data['name'])) {
                    $this->addSystemLog('CREATE', 'SETTING', "เพิ่มข้อมูลตาราง {$table} (ชื่อ: {$data['name']})", null, $data);
                    echo json_encode(["message" => "เพิ่มข้อมูลสำเร็จ"]);
                }
                break;
            case 'update':
                if ($settingModel->update($table, $data['id'], $data['name'])) {
                    $this->addSystemLog('UPDATE', 'SETTING', "แก้ไขข้อมูลตาราง {$table} (ID: {$data['id']})", null, $data);
                    echo json_encode(["message" => "แก้ไขข้อมูลสำเร็จ"]);
                }
                break;
            case 'delete':
                if ($settingModel->delete($table, $data['id'])) {
                    $this->addSystemLog('DELETE', 'SETTING', "ลบข้อมูลตาราง {$table} (ID: {$data['id']})", $data, null);
                    echo json_encode(["message" => "ลบข้อมูลสำเร็จ"]);
                }
                break;  

            case 'create_venname':
                if ($settingModel->createVenName($data)) {
                    $this->addSystemLog('CREATE', 'SETTING', "เพิ่มข้อมูลกลุ่มเวรหลักใหม่", null, $data);
                    echo json_encode(["message" => "เพิ่มข้อมูลเวรสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถเพิ่มข้อมูลได้"]);
                }
                break;

            case 'update_venname':
                if ($settingModel->updateVenName($data)) {
                    $this->addSystemLog('UPDATE', 'SETTING', "แก้ไขข้อมูลกลุ่มเวรหลัก", null, $data);
                    echo json_encode(["message" => "แก้ไขข้อมูลเวรสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]);
                }
                break;

            case 'create_sub':
                if ($settingModel->createVenSub($data)) {
                    $this->addSystemLog('CREATE', 'SETTING', "เพิ่มหน้าที่ย่อยการเข้าเวรใหม่", null, $data);
                    echo json_encode(["message" => "เพิ่มหน้าที่ย่อยสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถเพิ่มข้อมูลได้"]);
                }
                break;
            case 'update_sub':
                if ($settingModel->updateVenSub($data)) {
                    $this->addSystemLog('UPDATE', 'SETTING', "แก้ไขหน้าที่ย่อยการเข้าเวร", null, $data);
                    echo json_encode(["message" => "แก้ไขหน้าที่ย่อยสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]);
                }
                break;
            case 'update_order':
                if ($settingModel->updateSubDutyOrder($data)){
                    $this->addSystemLog('UPDATE', 'SETTING', "แก้ไขการจัดเรียงลำดับหน้าที่ (Sort Order)", null, $data);
                    echo json_encode(["message" => "แก้ไขลำดับหน้าที่สำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]);
                }
                break;

            case 'get_by_id':
                $id = $_GET['id'] ?? $data['id'];
                $result = $settingModel->getById($table, $id);
                echo json_encode($result);
                break;

            case 'get_agency_config':            
                $result = $settingModel->getAgencyConfig();
                echo json_encode($result);
                break;
        }
    }

    public function forceUpdateChangeStatus() {
        // รับข้อมูล JSON จาก Frontend
        $data = json_decode(file_get_contents("php://input"));
        
        $change_id = $data->change_id ?? null;
        $status = $data->status ?? null; // 0=รออนุมัติ, 1=อนุมัติ, 2=ไม่อนุมัติ

        if (!$change_id || $status === null) {
            echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน']);
            return;
        }

        require_once '../src/Models/SettingModel.php';
        $settingModel = new SettingModel($this->db);
        
        // ส่งข้อมูลไปอัปเดตใน Model
        $result = $settingModel->updateChangeStatus($change_id, $status);
        
        if ($result) {
            // 🌟 บันทึก Log การจัดการเปลี่ยนเวร
            $statusText = ($status == 1) ? 'อนุมัติ' : (($status == 2) ? 'ไม่อนุมัติ' : 'รออนุมัติ');
            $this->addSystemLog('UPDATE', 'TRANSFER', "อัปเดตสถานะใบเปลี่ยนเวร (ID อ้างอิง: {$change_id}, ตั้งค่าเป็น: {$statusText})", null, ["change_id" => $change_id, "status" => $statusText]);

            echo json_encode([
                'success' => true, 
                'message' => 'อัปเดตสถานะและจัดการตารางเวรเรียบร้อยแล้ว'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => $result['error'] ?? 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
            ]);
        }
    }

    public function getUsersBySubId($sub_id) {
        require_once '../src/Models/SettingModel.php';
        $settingModel = new SettingModel($this->db);
        $users = $settingModel->getUsersBySubId($sub_id);
        
        // ส่งกลับไปเป็น JSON
        http_response_code(200);
        echo json_encode($users); 
        exit;
    }

    // 🌟 [เพิ่มใหม่] ดึงเวลาอัปเดตล่าสุดจาก Log ของการเปลี่ยนเวรและการอนุมัติ
    public function getLatestUpdate() {
        try {
            // ดึงเวลาล่าสุด (MAX) จากโมดูล APPROVE และ TRANSFER
            $stmt = $this->db->prepare("
                SELECT created_at 
                FROM system_logs 
                WHERE module IN ('APPROVE','SCHEDULE', 'TRANSFER') 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "latest_update" => $result ? $result['created_at'] : null
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Server Error: " . $e->getMessage()]);
        }
    }
}
?>