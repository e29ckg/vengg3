<?php
// backend/src/Controllers/SettingController.php

require_once '../src/Models/Setting.php';

class SettingController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleRequest($action, $table) {
        $settingModel = new Setting($this->db);
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
            case 'get_by_id':
                $id = $_GET['id'] ?? $data['id'];
                $result = $settingModel->getById($table, $id);
                echo json_encode($result);
                break;
        }
    }
}