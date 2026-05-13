<?php
class VenSettingController {
    private $settingModel;

    public function __construct($settingModel) {
        $this->settingModel = $settingModel;
    }

    public function handleAction($action, $data) {
        switch ($action) {
            case 'ven_full':
                echo json_encode([
                    "success" => true,
                    "data" => $this->settingModel->getVenFullData()
                ]);
                break;
                
            case 'ven_name_list':
                echo json_encode([
                    "success" => true,
                    "data" => $this->settingModel->getVenFullData()
                ]);
                break;

            case 'list_venname':
                echo json_encode([
                    "success" => true,
                    "data" => $this->settingModel->getVenNames()
                ]);
                break;

            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                echo json_encode($this->settingModel->getVenNameById($id));
                break;

            case 'create_venname':
            case 'update_venname':
                echo json_encode(["success" => $this->settingModel->saveVenName($data)]);
                break;

            case 'delete_ven_name':
                echo json_encode(["success" => $this->settingModel->deleteVenName($data['id'])]);
                break;

            case 'create_sub':
            case 'update_sub':
                echo json_encode(["success" => $this->settingModel->saveSubDuty($data)]);
                break;

            case 'delete_sub_duty':
                echo json_encode(["success" => $this->settingModel->deleteSubDuty($data['id'])]);
                break;

            case 'update_order':
                echo json_encode(["success" => $this->settingModel->updateSubDutyOrder($data)]);
                break;

            default:
                http_response_code(404);
                echo json_encode(["error" => "Action not found"]);
                break;
        }
    }
}