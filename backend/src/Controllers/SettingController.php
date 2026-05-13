<?php
class SettingController {
    private $settingModel;

    public function __construct($settingModel) {
        $this->settingModel = $settingModel;
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

    // 🌟 ดึงข้อมูลตั้งค่าหน่วยงาน
    public function getAgencySettings() {
        $settings = $this->settingModel->getAgencySettings();
        
        // คืนค่าเป็น JSON (ถ้าไม่มีข้อมูลให้คืนเป็น Array ว่างหรือ Default)
        echo json_encode($settings ? $settings : []);
    }

    // 🌟 อัปเดตข้อมูลตั้งค่าหน่วยงาน
    public function updateAgencySettings() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($this->settingModel->updateAgencySettings($data)) {
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
                    echo json_encode(["message" => "เพิ่มข้อมูลสำเร็จ"]);
                }
                break;
            case 'update':
                if ($settingModel->update($table, $data['id'], $data['name'])) {
                    echo json_encode(["message" => "แก้ไขข้อมูลสำเร็จ"]);
                }
                break;
            case 'delete':
                if ($settingModel->delete($table, $data['id'])) {
                    echo json_encode(["message" => "ลบข้อมูลสำเร็จ"]);
                }
                break;  

            case 'create_venname':
                if ($settingModel->createVenName($data)) {
                    echo json_encode(["message" => "เพิ่มข้อมูลเวรสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถเพิ่มข้อมูลได้"]);
                }
                break;

            case 'update_venname':
                if ($settingModel->updateVenName($data)) {
                    echo json_encode(["message" => "แก้ไขข้อมูลเวรสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]);
                }
                break;

            case 'create_sub':
                if ($settingModel->createVenSub($data)) {
                    echo json_encode(["message" => "เพิ่มหน้าที่ย่อยสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถเพิ่มข้อมูลได้"]);
                }
                break;
            case 'update_sub':
                if ($settingModel->updateVenSub($data)) {
                    echo json_encode(["message" => "แก้ไขหน้าที่ย่อยสำเร็จ"]);
                } else {
                    http_response_code(500); echo json_encode(["error" => "ไม่สามารถแก้ไขข้อมูลได้"]);
                }
                break;
            case 'update_order':
                if ($settingModel->updateSubDutyOrder($data)){
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

        // 🌟 แก้ไขตรงนี้: เรียกใช้งาน Model ให้ถูกต้องเหมือนฟังก์ชันอื่นๆ ในคลาส
        require_once '../src/Models/SettingModel.php';
        $settingModel = new SettingModel($this->db);
        
        // ส่งข้อมูลไปอัปเดตใน Model
        $result = $settingModel->updateChangeStatus($change_id, $status);
        
        if ($result) {
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



    
}