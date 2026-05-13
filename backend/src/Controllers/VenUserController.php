<?php
class VenUserController {
    private $settingModel;

    public function __construct($settingModel) {
        $this->settingModel = $settingModel;
    }

    // ดึงรายชื่อคนตามหน้าที่ย่อย
    public function getBySub($sub_id) {
        if (!$sub_id) {
            http_response_code(400);
            echo json_encode(["error" => "Missing sub_id parameter"]);
            return;
        }
        echo json_encode($this->settingModel->getUsersBySubId($sub_id));
    }

    // เพิ่มคนเข้าหน้าที่ย่อย
    public function add($data) {
        if (!isset($data['sub_id']) || !isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }
        echo json_encode(["success" => $this->settingModel->addVenUser($data['sub_id'], $data['user_id'])]);
    }

    // ลบคนออกจากหน้าที่ย่อย
    public function remove($data) {
        if (!isset($data['vu_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }
        echo json_encode(["success" => $this->settingModel->removeVenUser($data['vu_id'])]);
    }

    // บันทึกลำดับ (Drag & Drop)
    public function updateOrder($data) {
        if (!isset($data['ordered_ids'])) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }
        echo json_encode(["success" => $this->settingModel->updateVenUserOrder($data['ordered_ids'])]);
    }
}