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
        AuthMiddleware::checkDirector($connection);
        
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
    
    case 'admin/user/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->deleteUser();
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
        AuthMiddleware::checkDirector($connection); // 🔒 ยาม VIP
        $table = $_GET['table'] ?? ''; // dep หรือ group
        $action = $_GET['action'] ?? 'list';
        
        
        $controller = new SettingController($connection);
        $controller->handleRequest($action, $table);
        break;

    case 'admin/ven_user':
        AuthMiddleware::checkDirector($connection);
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
        AuthMiddleware::checkDirector($connection);
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
        }elseif ($action === 'update_status') {
            if ($settingModel->updateCommandStatus($data['id'], $data['status'])) {
                echo json_encode(["success" => true, "message" => "อัปเดตสถานะสำเร็จ"]);
            } else {
                http_response_code(500); 
                echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
            }
        }
        break;

    case 'admin/ven_schedule':
        AuthMiddleware::checkDirector($connection);
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
    
    case 'admin/ven_time':
        // ถ้าต้องการทำระบบเพิ่มลบแก้ในอนาคต ก็มาเขียนเงื่อนไขเพิ่มตรงนี้ได้
        // แต่ตอนนี้ใช้ดึงข้อมูล (List) อย่างเดียวไปก่อนครับ
        AuthMiddleware::checkDirector($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        echo json_encode($settingModel->getVenTimes());
        break;

    case 'admin/agency_config':
        AuthMiddleware::checkDirector($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);

        if ($action === 'get') {
            echo json_encode($settingModel->getAgencyConfig());
        } elseif ($action === 'update') {
            if ($settingModel->updateAgencyConfig($data)) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Update failed"]);
            }
        }
        break;

    case 'admin/ven_approve':
        // ตรวจสอบสิทธิ์ (ใช้ Middleware เดียวกับ admin)
        AuthMiddleware::checkDirector($connection);
        $settingModel = new Setting($connection);
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($action === 'list') {
            echo json_encode($settingModel->getAllChangeRequests());
        } 
        elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['id']) && isset($data['status'])) {
                if ($settingModel->updateChangeStatus($data['id'], $data['status'])) {
                    echo json_encode(["success" => true, "message" => "อัปเดตสถานะเรียบร้อย"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
                }
            }
        }
        break;
    
    case 'settings/update':
        // 🌟 ด่านตรวจ: ถ้าไม่ใช่ Admin ระบบจะส่ง 403 Forbidden กลับไปทันที
        AuthMiddleware::checkAdmin($connection); 

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['setting_key']) && isset($data['setting_value'])) {
            $stmt = $connection->prepare("UPDATE app_settings SET setting_value = :val WHERE setting_key = :key");
            $success = $stmt->execute([
                ':val' => $data['setting_value'],
                ':key' => $data['setting_key']
            ]);
            
            echo json_encode(["success" => $success]);
        }
        break;

    case 'settings/app':
        AuthMiddleware::checkToken($connection); // ต้องล็อกอินก่อน
        
        // ดึงการตั้งค่าทั้งหมดออกมาเป็น Array อัตโนมัติ
        $stmt = $connection->query("SELECT setting_key, setting_value FROM app_settings");
        $settings = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        http_response_code(200);
        echo json_encode($settings);
        break;
    
    // ------------------------------------------------
    case 'ven/eligible_users':        
        require_once '../src/Controllers/SettingController.php';
        $settingController = new SettingController($connection);
        
        $action = $_GET['action'] ?? '';
        if ($action === 'get_by_sub') {
            // ดึงคนที่มีหน้าที่ตรงกัน โดยไม่ต้องเช็คสิทธิ์ Admin
            AuthMiddleware::checkToken($connection); // เช็คแค่ล็อกอินแล้วพอ
            
            $sub_id = $_GET['sub_id'] ?? null;
            if ($sub_id) {
                $settingController->getUsersBySubId($sub_id);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Missing sub_id"]);
            }
        }
        break;

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

    // 🌟 เพิ่มเคสใหม่ สำหรับยกเลิกใบเปลี่ยนเวร
        case 'ven/cancel_change':
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['change_id'])) {
                $ven = new Ven($connection); // ปรับชื่อ Class ให้ตรงกับที่คุณใช้เก็บฟังก์ชัน
                $success = $ven->cancelShiftChange($data['change_id']);
                
                if ($success) {
                    echo json_encode(["success" => true]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "ไม่สามารถยกเลิกได้ (ใบเปลี่ยนนี้อาจถูกอนุมัติไปแล้ว)"]);
                }
            }
            break;

    case 'auth/me':
        require_once '../src/Controllers/AuthController.php';
        $authController = new AuthController($connection);
        $authController->getMe();
        break;
        
    

    case 'user/transfer':
        // 1. ตรวจสอบสิทธิ์ (ต้องล็อกอินถึงจะโอนเวรได้)
        AuthMiddleware::checkToken($connection);

        // 2. รับข้อมูล JSON ที่ Frontend ส่งมาด้วย axios/api.post
        $data = json_decode(file_get_contents("php://input"), true);
        $action = $_GET['action'] ?? '';

        if ($action === 'perform') {
            // 3. ตรวจสอบว่าส่ง id มาครบหรือไม่
            if (empty($data['schedule_id']) || empty($data['new_user_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน (ต้องการ schedule_id และ new_user_id)"]);
                exit;
            }

            // 4. เรียกใช้ Model (สมมติว่าคุณใช้ Setting Model หรือเปลี่ยนเป็น Model ที่คุณใช้จัดการเวร)
            require_once '../src/Models/Setting.php'; 
            $settingModel = new Setting($connection);

            // 5. ทำการอัปเดตข้อมูล
            if ($settingModel->transferShift($data['schedule_id'], $data['new_user_id'])) {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "โอนเวรเรียบร้อย"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถโอนเวรได้ เกิดข้อผิดพลาดที่ฐานข้อมูล"]);
            }
        }
        break;

    case 'report/monthly':
        // ตรวจสอบ Token ก่อนเข้าถึง API
        $currentUser = AuthMiddleware::checkToken($connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $month = isset($_GET['month']) ? $_GET['month'] : null;
        $year = isset($_GET['year']) ? $_GET['year'] : null;

        if (isset($_GET['month']) && isset($_GET['year'])) {            
            require_once '../src/Controllers/ReportController.php';
            $creportController = new ReportController($connection);
            $monthYear = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT); // รวมเป็นรูปแบบ YYYY-MM
            $creportController->getCommonReport($monthYear);    
                   
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing month or year parameter (expected format: YYYY-MM)"]);
        }
        break;

    case 'report/schedule-details':
            //$currentUser = AuthMiddleware::checkToken($connection);
            
            $command_id = isset($_GET['command_id']) ? $_GET['command_id'] : null;
                       
            if ($command_id) {            
                require_once '../src/Controllers/ReportController.php';
                $creportController = new ReportController($connection);
                $creportController->getScheduleDetails($command_id);            
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Missing command_id parameter"]);
            }
            break;

    

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}
?>