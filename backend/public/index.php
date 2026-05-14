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

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/Controllers/VenController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/SettingController.php';
require_once __DIR__ . '/../src/Models/GoogleSettingModel.php';
require_once __DIR__ . '/../src/Controllers/GoogleSettingController.php';
require_once __DIR__ . '/../src/Models/SettingModel.php';
require_once __DIR__ . '/../src/Models/ReportModel.php';
require_once __DIR__ . '/../src/Models/FinanceModel.php';
require_once __DIR__ . '/../src/Models/BackupModel.php';
require_once __DIR__ . '/../src/Models/VenTransferModel.php';
require_once __DIR__ . '/../src/Models/VenApproveModel.php';
require_once __DIR__ . '/../src/Controllers/VenTransferController.php';
require_once __DIR__ . '/../src/Controllers/OptionController.php';
require_once __DIR__ . '/../src/Controllers/VenApproveController.php';
require_once __DIR__ . '/../src/Controllers/VenUserController.php';
require_once __DIR__ . '/../src/Controllers/VenCommandController.php';
require_once __DIR__ . '/../src/Controllers/VenScheduleController.php';
require_once __DIR__ . '/../src/Controllers/VenSettingController.php';
require_once __DIR__ . '/../src/Controllers/ReportController.php';
require_once __DIR__ . '/../src/Controllers/FinanceController.php';
require_once __DIR__ . '/../src/Controllers/BackupController.php';
require_once __DIR__ . '/../src/Controllers/TelegramController.php';
require_once __DIR__ . '/../src/Services/TelegramService.php';
require_once __DIR__ . '/../src/Services/GoogleCalendarService.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';


$route = isset($_GET['route']) ? $_GET['route'] : '';

$db = new Database();
$connection = $db->getConnection();

// --- ระบบ Routing ---
switch ($route) {
    case 'test':
        echo json_encode(["status" => "success", "message" => "Backend is working!"]);
        break;

    case 'auth/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new AuthController($connection);
            $controller->login();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed. Use POST."]);
        }
        break;

    // ==========================================
    // 🌟 ข้อมูลโปรไฟล์ผู้ใช้งาน
    // ==========================================
    case 'user/profile':
        $userData = AuthMiddleware::checkToken($connection);
        $userId = is_array($userData) ? $userData['id'] : $userData->id;
        
        $userModel = new User($connection); 
        $userController = new UserController($userModel);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userController->getProfile($userId);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // ==========================================
    // 🌟 อัปเดตข้อมูลโปรไฟล์
    // ==========================================
    case 'user/profile/update':
        $userData = AuthMiddleware::checkToken($connection);
        $userId = is_array($userData) ? $userData['id'] : $userData->id;
        
        $userModel = new User($connection);
        $userController = new UserController($userModel);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $userController->updateProfile($userId);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // 🌟 เปลี่ยนรหัสผ่าน
    case 'user/profile/password':
        $userData = AuthMiddleware::checkToken($connection);
        $userId = is_array($userData) ? $userData['id'] : $userData->id;
        $data = json_decode(file_get_contents("php://input"), true);

        // 1. ดึงรหัสเดิมมาเช็ค
        $stmt = $connection->prepare("SELECT password_hash FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data['old_password'], $user['password_hash'])) {
            $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $stmtUpdate = $connection->prepare("UPDATE user SET password_hash = ? WHERE id = ?");
            $stmtUpdate->execute([$newHash, $userId]);
            
            echo json_encode(["success" => true, "message" => "Password changed"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "รหัสผ่านปัจจุบันไม่ถูกต้อง"]);
        }
        break;

    case 'admin/user/list':
        AuthMiddleware::checkDirector($connection);        
        $controller = new UserController(new User($connection));
        $controller->listUsers();
        break;
    
    case 'admin/user/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection);
            $controller = new UserController(new User($connection));            
            $controller->createUser();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed. Use POST."]);
        }
        break;
    
    case 'admin/user/status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); 
            $controller = new UserController(new User($connection));
            $controller->changeStatus();
        }
        break;

    case 'admin/user/update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController(new User($connection));
            $controller->updateUser();
        }
        break;
    
    case 'admin/users/update_order':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController(new User($connection));
            $controller->update_order();
        }
        break;
    
    case 'admin/user/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController(new User($connection));
            $controller->deleteUser();
        }
        break;

    case 'admin/options/get':
        AuthMiddleware::checkDirector($connection);
        // 🌟 เปลี่ยนกลับมาส่ง SettingModel เข้าไป
        $optionController = new OptionController(new SettingModel($connection));
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $optionController->getOptions();
        }
        break;

    case 'admin/options/add':
        AuthMiddleware::checkAdmin($connection);
        $optionController = new OptionController(new SettingModel($connection));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $optionController->addOption();
        }
        break;

    case 'admin/options/delete':
        AuthMiddleware::checkAdmin($connection);
        $optionController = new OptionController(new SettingModel($connection));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $optionController->deleteOption();
        }
        break;
   
    
    case 'admin/ven_time':
        AuthMiddleware::checkDirector($connection);
        $settingModel = new SettingModel($connection);
        echo json_encode($settingModel->getVenTimes());
        break;    

    // ==========================================
    // จัดการคำสั่งเวร (Ven Command)
    // ==========================================
    case 'admin/ven_com/list':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $cmdController->getList();
        break;

    case 'admin/ven_com/create':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $cmdController->create($data);
        break;

    case 'admin/ven_com/update':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $cmdController->update($data);
        break;

    case 'admin/ven_com/delete':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $cmdController->delete($_GET['id'] ?? null);
        break;

    case 'admin/ven_com/toggle_status':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $cmdController->toggleStatus($data);
        break;

    case 'admin/ven_com/update_status':
        AuthMiddleware::checkDirector($connection);
        $cmdController = new VenCommandController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $cmdController->updateStatus($data);
        break;      

    
    // ดึงประวัติการเปลี่ยนเวรส่วนตัว
    case 'user/ven_change_history':
        $userData = AuthMiddleware::checkToken($connection);
        $user_id = is_array($userData) ? ($userData['id'] ?? null) : ($userData->id ?? null);
        if (!$user_id) {
            http_response_code(401);
            echo json_encode(["error" => "ไม่พบข้อมูลผู้ใช้ใน Token"]);
            exit;
        }
        $settingModel = new SettingModel($connection);
        echo json_encode($settingModel->getUserChangeHistory($user_id));
        break;

    case 'ven/list':
        $currentUser = AuthMiddleware::checkToken($connection);
        $month = isset($_GET['month']) ? $_GET['month'] : null;        
        $controller = new VenController($connection);
        $controller->getList($month);
        break;

    case 'ven/detail':
        $currentUser = AuthMiddleware::checkToken($connection);        
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $controller = new VenController($connection);
        $controller->getDetail($id);
        break;


    // ==========================================
    // 🌟 ดึงรายชื่อผู้มีสิทธิ์รับเวร (สำหรับพนักงานทั่วไป)
    // ==========================================
    case 'ven/eligible_users/get_by_sub':
        AuthMiddleware::checkToken($connection); 
        $userController = new VenUserController(new SettingModel($connection));        
        $userController->getBySub($_GET['sub_id'] ?? null);
        break;

    // ==========================================
    // 🌟 ระบบโอนเวร / สลับเวร
    // ==========================================
    case 'ven/transfer/perform':
        $userData = AuthMiddleware::checkToken($connection);
        $currentUserId = is_array($userData) ? $userData['id'] : $userData->id;
        $transferModel = new VenTransferModel($connection);
        $transferController = new VenTransferController($transferModel, $connection);        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $transferController->perform($currentUserId, $data);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // 🌟 สำหรับยกเลิกใบเปลี่ยนเวร
    case 'ven/transfer/cancel':
        AuthMiddleware::checkToken($connection);        
        $transferModel = new VenTransferModel($connection);
        $transferController = new VenTransferController($transferModel, $connection);        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $transferController->cancel($data);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    case 'auth/me':
        require_once '../src/Controllers/AuthController.php';
        $authController = new AuthController($connection);
        $authController->getMe();
        break;
            
    // 🌟 อัปโหลดรูปโปรไฟล์
    case 'user/profile/upload_avatar':
        $userData = AuthMiddleware::checkToken($connection);
        $userId = is_array($userData) ? $userData['id'] : $userData->id;
        $userModel = new User($connection);
        $userController = new UserController($userModel);
        $file = isset($_FILES['avatar']) ? $_FILES['avatar'] : null;
        $userController->uploadAvatar($userId, $file, __DIR__);
        break;

    case 'admin/ven/setting':
        AuthMiddleware::checkAdmin($connection);            
        $settingModel = new SettingModel($connection); 
        $venSettingController = new VenSettingController($settingModel);            
        $action = $_GET['action'] ?? '';
        $data = json_decode(file_get_contents("php://input"), true);
        $venSettingController->handleAction($action, $data);
        break;

    // ==========================================
    // 🌟 การจัดการผู้อยู่เวร (ผูกคนเข้ากับหน้าที่ย่อย)
    // ==========================================    
    case 'admin/ven_user/get_by_sub':
        AuthMiddleware::checkDirector($connection);
        $userController = new VenUserController(new SettingModel($connection));
        $userController->getBySub($_GET['sub_id'] ?? null);
        break;

    case 'admin/ven_user/add':
        AuthMiddleware::checkDirector($connection);
        $userController = new VenUserController(new SettingModel($connection));
        $data = json_decode(file_get_contents("php://input"), true);
        $userController->add($data);
        break;

    case 'admin/ven_user/remove':
        AuthMiddleware::checkDirector($connection);
        $userController = new VenUserController(new SettingModel($connection));
        $data = json_decode(file_get_contents("php://input"), true);
        $userController->remove($data);
        break;

    case 'admin/ven_user/update_order':
        AuthMiddleware::checkDirector($connection);
        $userController = new VenUserController(new SettingModel($connection));
        $data = json_decode(file_get_contents("php://input"), true);
        $userController->updateOrder($data);
        break;

    // ==========================================
    // 🌟 จัดการตารางเวร (Ven Schedule)
    // ==========================================
    case 'admin/ven_schedule/list_month':
        AuthMiddleware::checkDirector($connection);
        $schController = new VenScheduleController(new SettingModel($connection), $connection);
        $schController->listMonth($_GET['month'] ?? '');
        break;

    case 'admin/ven_schedule/add':
        AuthMiddleware::checkDirector($connection);
        $schController = new VenScheduleController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $schController->add($data);
        break;

    case 'admin/ven_schedule/remove':
        AuthMiddleware::checkDirector($connection);
        $schController = new VenScheduleController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $schController->remove($data['id'] ?? null);
        break;

    case 'admin/ven_schedule/sync_google':
        AuthMiddleware::checkDirector($connection);
        $schController = new VenScheduleController(new SettingModel($connection), $connection);
        $data = json_decode(file_get_contents("php://input"), true);
        $schController->syncGoogle($data, __DIR__);
        break;    

    case 'admin/ven_schedule/user_list_by_sub':
        AuthMiddleware::checkDirector($connection);            
        $schController = new VenScheduleController(new SettingModel($connection), $connection);
        $schController->getUserListBySub($_GET['sub_id'] ?? null);
        break;

    case 'admin/ven_schedule/ven_name_list':
        AuthMiddleware::checkAdmin($connection);            
        $settingModel = new SettingModel($connection); 
        $venSettingController = new VenSettingController($settingModel);   
        $data = json_decode(file_get_contents("php://input"), true);
        $venSettingController->handleAction('ven_name_list', $data);
        break;


    // ==========================================
    // ⚙️ การอำนวยการ ออกรายงาน (Report)
    // ==========================================
    case 'report/monthly':
        AuthMiddleware::checkToken($connection);            
        $reportModel = new ReportModel($connection);
        $reportController = new ReportController($reportModel);            
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;            
        if ($month && $year) {
            $monthYear = $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT);
            $reportController->getCommonReport($monthYear);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing month or year parameter"]);
        }
        break;

    case 'report/schedule-details':
        AuthMiddleware::checkToken($connection);             
        $reportModel = new ReportModel($connection);
        $reportController = new ReportController($reportModel);            
        $command_id = $_GET['command_id'] ?? null;            
        if ($command_id) {
            $reportController->getScheduleDetails($command_id);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Missing command_id parameter"]);
        }
        break;

    // ==========================================
    // 🌟 จัดการคำขออนุมัติเวร (Ven Approve)
    // ==========================================
    case 'admin/ven_approve/list':
        AuthMiddleware::checkAdmin($connection);
        $approveController = new VenApproveController(new VenApproveModel($connection));
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $approveController->getList();
        }
        break;

    case 'admin/ven_approve/force_update':
        AuthMiddleware::checkAdmin($connection);
        $approveController = new VenApproveController(new VenApproveModel($connection));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $approveController->forceUpdate($data);
        }
        break;

    // ==========================================
    // ⚙️ การเงิน ออกรายงาน (Financ Report)
    // ==========================================
    case 'finance/report':
        AuthMiddleware::checkToken($connection); 
        $financeModel = new FinanceModel($connection);
        $financeController = new FinanceController($financeModel);
        $month = $_GET['month'] ?? date('Y-m');
        $command_id = $_GET['command_id'] ?? null;
        $financeController->getFinanceReport($month, $command_id);
        break;

    case 'get_commands':
        AuthMiddleware::checkToken($connection);        
        $financeModel = new FinanceModel($connection);
        $financeController = new FinanceController($financeModel);        
        $month = $_GET['month'] ?? date('Y-m');
        $financeController->getCommands($month);
        break;
    
    // ==========================================
    // ⚙️ ดึงข้อมูลการตั้งค่าระบบ (สำหรับพนักงานทั่วไปใช้อ่านกฎ)
    // ==========================================
    case 'system_settings':
        AuthMiddleware::checkToken($connection);
        $settingModel = new SettingModel($connection); 
        $settingController = new SettingController($settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $settingController->getSettings();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // ==========================================
    // ⚙️ ตั้งค่าระบบ (System Settings)
    // ==========================================
    case 'admin/system_settings':
        AuthMiddleware::checkAdmin($connection);        
        $settingModel = new SettingModel($connection);
        $settingController = new SettingController($settingModel);        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $settingController->getSettings();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingController->updateSettings();
        } else {
            http_response_code(405); 
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // ==========================================
    // 🌟 ตั้งค่าหน่วยงานและผู้ลงนาม (Agency Settings)
    // ==========================================
    case 'admin/agency_settings':
        $settingModel = new SettingModel($connection);
        $settingController = new SettingController($settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddleware::checkToken($connection); 
            $settingController->getAgencySettings();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection);
            $settingController->updateAgencySettings();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    case 'admin/settings/update_toggle':
        AuthMiddleware::checkAdmin($connection); 
        $settingModel = new SettingModel($connection);
        $settingController = new SettingController($settingModel);        
        $settingController->updateToggle();
        break;
        
    // 🌟 สำรองข้อมูล (เฉพาะรูปภาพ .zip)
    case 'admin/backup/images':
        AuthMiddleware::checkAdmin($connection);
        $backupModel = new BackupModel($connection);
        $backupController = new BackupController($backupModel);        
        $backupController->downloadImages(__DIR__);
        break;

    // 🌟 สำรองข้อมูล (เฉพาะฐานข้อมูล .sql)
    case 'admin/backup/sql':
        AuthMiddleware::checkAdmin($connection);
        $backupModel = new BackupModel($connection);
        $backupController = new BackupController($backupModel);
        $backupController->downloadSql();
        break;

    // ==========================================
    // 🌟 การตั้งค่า Google Calendar
    // ==========================================
    case 'admin/google_settings/get':
        AuthMiddleware::checkAdmin($connection);
        $googleController = new GoogleSettingController(new GoogleSettingModel($connection));
        $googleController->getConfig();
        break;

    case 'admin/google_settings/update':
        AuthMiddleware::checkAdmin($connection);
        $googleController = new GoogleSettingController(new GoogleSettingModel($connection));
        $data = json_decode(file_get_contents("php://input"), true);
        $googleController->updateConfig($data);
        break;

    case 'admin/google_settings/upload':
        AuthMiddleware::checkAdmin($connection);
        $googleController = new GoogleSettingController(new GoogleSettingModel($connection));
        // ส่งไฟล์อัปโหลด และ path __DIR__ ไปให้ Controller จัดการ
        $file = $_FILES['credential_file'] ?? null;
        $googleController->uploadCredentials($file, __DIR__);
        break;

    // ==========================================
    // 📢 ระบบตั้งค่าการแจ้งเตือน Telegram
    // ==========================================   
    case 'admin/telegram_settings':
        AuthMiddleware::checkAdmin($connection);
        $settingModel = new SettingModel($connection);
        $telegramController = new TelegramController($connection, $settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $telegramController->getSettings();
        }
        break;

    // บันทึกการตั้งค่าลง Database
    case 'admin/telegram_settings/update':
        AuthMiddleware::checkAdmin($connection);
        $settingModel = new SettingModel($connection);
        $telegramController = new TelegramController($connection, $settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $telegramController->updateSettings();
        }
        break;

    // ทดสอบยิงข้อความเข้ากลุ่ม Telegram
    case 'admin/telegram_settings/test':
        AuthMiddleware::checkAdmin($connection);
        $settingModel = new SettingModel($connection); // โยนเผื่อไว้ใน Construct
        $telegramController = new TelegramController($connection, $settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $telegramController->testMessage();
        }
        break;

    // ยิงแจ้งเตือนรายชื่อเวรแบบ Manual (วันนี้/พรุ่งนี้)
    case 'admin/telegram_settings/manual_notify':
        AuthMiddleware::checkAdmin($connection);
        $settingModel = new SettingModel($connection);
        $telegramController = new TelegramController($connection, $settingModel);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // โหลด Service แล้วส่งไปให้ Controller ใช้
            $telegramService = new TelegramService($connection);
            $telegramController->manualNotify($telegramService);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}

?>