<?php
// backend/src/Controllers/OptionController.php

require_once __DIR__ . '/../Models/SettingModel.php';

class OptionController {
    
    private $settingModel;

    // 🌟 รับ $settingModel เข้ามาใช้งาน
    public function __construct($settingModel) {
        $this->settingModel = $settingModel;
    }

    // ดึงข้อมูลส่งให้ Frontend
    public function getOptions() {
        $options = $this->settingModel->getUserOptions();
        http_response_code(200);
        echo json_encode($options);
    }

    // เพิ่มตัวเลือกใหม่
    public function addOption() {
        $data = json_decode(file_get_contents("php://input"), true);
        $type = $data['type'] ?? ''; 
        $value = trim($data['value'] ?? '');

        if (empty($type) || empty($value)) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }

        $keyMap = ['prefix' => 'prefixes', 'position' => 'positions', 'department' => 'departments'];
        if (!array_key_exists($type, $keyMap)) {
            http_response_code(400);
            echo json_encode(["error" => "ประเภทไม่ถูกต้อง"]);
            return;
        }

        $arrayKey = $keyMap[$type];
        $options = $this->settingModel->getUserOptions();

        if (!in_array($value, $options[$arrayKey])) {
            $options[$arrayKey][] = $value; 
            
            if ($this->settingModel->saveUserOptions($options)) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "มีข้อมูลนี้อยู่แล้วในระบบ"]);
        }
    }

    // ลบตัวเลือก
    public function deleteOption() {
        $data = json_decode(file_get_contents("php://input"), true);
        $type = $data['type'] ?? '';
        $value = trim($data['value'] ?? '');

        $keyMap = ['prefix' => 'prefixes', 'position' => 'positions', 'department' => 'departments'];
        if (!array_key_exists($type, $keyMap)) {
            http_response_code(400);
            echo json_encode(["error" => "ประเภทไม่ถูกต้อง"]);
            return;
        }

        $arrayKey = $keyMap[$type];
        $options = $this->settingModel->getUserOptions();

        if (isset($options[$arrayKey])) {
            $options[$arrayKey] = array_values(array_filter($options[$arrayKey], function($item) use ($value) {
                return $item !== $value;
            }));

            if ($this->settingModel->saveUserOptions($options)) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถลบข้อมูลได้"]);
            }
        } else {
             http_response_code(400);
             echo json_encode(["error" => "ไม่พบข้อมูลที่ต้องการลบ"]);
        }
    }
}
?>