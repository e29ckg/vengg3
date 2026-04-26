<?php
// backend/public/index.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../src/config/database.php';
require_once '../src/Controllers/VenController.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/UserController.php';
require_once '../src/Controllers/SettingController.php';
require_once '../src/Middleware/AuthMiddleware.php';


$route = isset($_GET['route']) ? $_GET['route'] : '';

$db = new Database();
$connection = $db->getConnection();

// --- ระบบ Routing ---
switch ($route) {
    case 'test':
        echo json_encode(["status" => "success", "message" => "Backend is working!"]);
        break;

    case 'auth/login':
        // บังคับให้เป็น POST method เท่านั้น
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new AuthController($connection);
            $controller->login();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed. Use POST."]);
        }
        break;

    case 'admin/user/list':
        // 🔒 เรียกใช้ยาม VIP (ต้องเป็น Admin เท่านั้นถึงผ่านได้)
        AuthMiddleware::checkAdmin($connection);
        
        $controller = new UserController($connection);
        $controller->listUsers();
        break;
    
    case 'admin/user/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 🔒 เรียกใช้ยาม VIP
            AuthMiddleware::checkAdmin($connection);
            
            $controller = new UserController($connection);
            $controller->createUser();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed. Use POST."]);
        }
        break;
    
    case 'admin/user/status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->changeStatus();
        }
        break;

    case 'admin/user/update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->updateUser();
        }
        break;

    case 'admin/user/options':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->getOptions();
        }
        break;

    // ใน switch ของ index.php
    case 'admin/setting':
        AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
        $table = $_GET['table'] ?? ''; // dep หรือ group
        $action = $_GET['action'] ?? 'list';
        
        
        $controller = new SettingController($connection);
        $controller->handleRequest($action, $table);
        break;

    case 'admin/ven_user':
        AuthMiddleware::checkAdmin($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);

        if ($action === 'get_by_sub') {
            echo json_encode($settingModel->getUsersBySubId($_GET['sub_id']));
        } elseif ($action === 'add') {
            echo json_encode(["success" => $settingModel->addVenUser($data['sub_id'], $data['user_id'])]);
        } elseif ($action === 'remove') {
            echo json_encode(["success" => $settingModel->removeVenUser($data['vu_id'])]);
        } elseif ($action === 'update_order') {
            echo json_encode(["success" => $settingModel->updateVenUserOrder($data['ordered_ids'])]);
        }
        break;

    case 'admin/ven_com':
        AuthMiddleware::checkAdmin($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);

        if ($action === 'list') {
            echo json_encode($settingModel->getVenCommands());
        } elseif ($action === 'create') {
            echo json_encode(["success" => $settingModel->createVenCommand($data)]);
        } elseif ($action === 'update') {
            echo json_encode(["success" => $settingModel->updateVenCommand($data)]);
        } elseif ($action === 'toggle_status') {
            echo json_encode(["success" => $settingModel->toggleVenCommandStatus($data['id'], $data['status'])]);
        } elseif ($action === 'delete') {
            echo json_encode(["success" => $settingModel->deleteVenCommand($data['id'])]);
        }
        break;

    case 'admin/ven_schedule':
        AuthMiddleware::checkAdmin($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);

        if ($action === 'list_month') {
            // โหลดข้อมูลตามเดือน (ส่ง YYYY-MM มาทาง GET)
            echo json_encode($settingModel->getSchedulesByMonth($_GET['month']));
            
        } elseif ($action === 'add') {
            // เพิ่มตารางเวร
            if ($settingModel->addSchedule($data)) {
                echo json_encode(["success" => true, "message" => "บันทึกเวรสำเร็จ"]);
            } else {
                http_response_code(500); echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
            }
            
        } elseif ($action === 'remove') {
            // ลบตารางเวร
            if ($settingModel->removeSchedule($data['id'])) {
                echo json_encode(["success" => true, "message" => "ลบเวรสำเร็จ"]);
            } else {
                http_response_code(500); echo json_encode(["error" => "ไม่สามารถลบข้อมูลได้"]);
            }
        } 
            
        break;
    // ------------------------------------------------

    case 'ven/list':
        // ตรวจสอบ Token ก่อนเข้าถึง API
        $currentUser = AuthMiddleware::checkToken($connection);

        // ถ้ามีการส่งเดือนมาทาง query string เช่น ?route=ven/list&month=2025-09
        $month = isset($_GET['month']) ? $_GET['month'] : null;
        
        $controller = new VenController($connection);
        $controller->getList($month);
        break;

    case 'ven/detail':
        // ตรวจสอบ Token ก่อนเข้าถึง API
        $currentUser = AuthMiddleware::checkToken($connection);
        
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $controller = new VenController($connection);
        $controller->getDetail($id);
        break;

    

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}
?>