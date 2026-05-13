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
require_once '../src/Controllers/OptionController.php';
require_once __DIR__ . '/../src/Models/GoogleSettingModel.php';
require_once __DIR__ . '/../src/Controllers/GoogleSettingController.php';
require_once __DIR__ . '/../src/Models/SettingModel.php';
require_once __DIR__ . '/../src/Models/ReportModel.php';
require_once __DIR__ . '/../src/Models/FinanceModel.php';
require_once __DIR__ . '/../src/Models/BackupModel.php';
require_once __DIR__ . '/../src/Models/VenTransferModel.php';
require_once __DIR__ . '/../src/Models/VenApproveModel.php';
require_once __DIR__ . '/../src/Controllers/HomeController.php';
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
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';

$db = new Database();
$connection = $db->getConnection();

// 🌟 ฟังก์ชันตัวช่วยสำหรับอัปเดต Google Calendar เฉพาะวันที่กำหนด
function updateGoogleCalendarDay($connection, $date) {
    // 1. ตรวจสอบไฟล์ Credentials และ Calendar ID
    $keyFilePath = __DIR__ . '/../src/Config/credentials.json';
    if (!file_exists($keyFilePath)) return false;

    $stmtConf = $connection->prepare("SELECT setting_value FROM google_service_settings WHERE setting_key = 'google_calendar_id'");
    $stmtConf->execute();
    $calId = $stmtConf->fetchColumn();
    if (!$calId) return false;

    // 2. ดึงข้อมูลตารางเวรล่าสุดของ "วันนั้น"
    $stmt = $connection->prepare("
        SELECT 
            vs.ven_date,
            vn.dn AS ven_time,
            vns.name AS sub_name,
            vn.name AS main_name,
            vs.google_event_id,
            CONCAT_WS(' ', CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, '')), p.last_name) AS user_name
        FROM ven_schedule vs
        LEFT JOIN ven_name_sub vns ON vs.ven_name_sub_id = vns.id
        LEFT JOIN ven_com vc ON vs.ven_com_id = vc.id
        LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id
        LEFT JOIN profile p ON vs.user_id = p.user_id 
        WHERE vs.ven_date = ?
        ORDER BY vn.srt ASC, vns.srt ASC;
    ");
    $stmt->execute([$date]);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($shifts)) return false;

    // 3. หา google_event_id ของวันนั้น
    $googleEventId = null;
    foreach ($shifts as $sch) {
        if (!empty($sch['google_event_id'])) {
            $googleEventId = $sch['google_event_id'];
            break;
        }
    }
    
    // หากวันนั้นไม่เคยถูกซิงค์ขึ้น Google มาก่อน ก็ข้ามการอัปเดตไป
    if (!$googleEventId) return false; 

    // 4. สร้างข้อความ Description ขึ้นมาใหม่จากข้อมูลล่าสุด
    $groupedByDuty = [];
    foreach ($shifts as $sch) {
        $dutyName = $sch['main_name'];
        if (!isset($groupedByDuty[$dutyName])) { $groupedByDuty[$dutyName] = []; }
        $groupedByDuty[$dutyName][] = $sch;
    }

    $description = "เวรประจำวันที่ " . date('d/m/Y', strtotime($date)) . "\n";
    foreach ($groupedByDuty as $dutyName => $dutyShifts) {
        $description .= "---------------------\n" . "📌". $dutyName . "\n";
        foreach ($dutyShifts as $sch) {
            $uName = trim($sch['user_name']) ?: "(ยังไม่มีผู้ลงเวร)";
            $description .= "👨‍💼 " . $uName . "\n";
        }
        $description .= "\n";
    }

    // 5. เชื่อมต่อ Google API และสั่งอัปเดตข้อมูล
    require_once __DIR__ . '/../vendor/autoload.php';
    $client = new Google_Client();
    $client->setAuthConfig($keyFilePath);
    $client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
    $service = new Google_Service_Calendar($client);

    try {
        // ดึง Event เดิมขึ้นมา
        $event = $service->events->get($calId, $googleEventId);
        // แก้ไขแค่รายละเอียด (รายชื่อ)
        $event->setDescription($description);
        // ส่งกลับไปทับของเดิม
        $service->events->update($calId, $googleEventId, $event);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

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

    // 🌟 ดึงข้อมูลโปรไฟล์
        case 'user/profile':
            $userData = AuthMiddleware::checkToken($connection);
            $userId = is_array($userData) ? $userData['id'] : $userData->id;
            
            // เพิ่มการดึง position, department, bank_account, bank_name
            $stmt = $connection->prepare("SELECT avatar, prefix_name, first_name, last_name, position, department, phone, bank_account, bank_comment FROM profile WHERE user_id = ?");
            $stmt->execute([$userId]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        // 🌟 อัปเดตข้อมูลโปรไฟล์
        case 'user/profile/update':
            $userData = AuthMiddleware::checkToken($connection);
            $userId = is_array($userData) ? $userData['id'] : $userData->id;
            $data = json_decode(file_get_contents("php://input"), true);

            // เพิ่มฟิลด์ใหม่ในคำสั่ง UPDATE
            $stmt = $connection->prepare("UPDATE profile SET prefix_name=?, first_name=?, last_name=?, position=?, department=?, phone=?, bank_account=?, bank_comment=? WHERE user_id=?");
            
            if ($stmt->execute([
                $data['prefix_name'] ?? null, 
                $data['first_name'] ?? null, 
                $data['last_name'] ?? null, 
                $data['position'] ?? null, 
                $data['department'] ?? null, 
                $data['phone'] ?? null, 
                $data['bank_account'] ?? null, 
                $data['bank_comment'] ?? null, 
                $userId
            ])) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Update failed"]);
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
        $controller = new UserController($connection);
        $controller->listUsers();
        break;
    
    case 'admin/user/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            AuthMiddleware::checkAdmin($connection); 
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
    
    case 'admin/users/update_order':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->update_order();
        }
        break;
    break;
    
    case 'admin/user/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::checkAdmin($connection); // 🔒 ยาม VIP
            $controller = new UserController($connection);
            $controller->deleteUser();
        }
        break;

    // 🌟 1. ดึงข้อมูลไปแสดงใน Dropdown และหน้าตั้งค่า (เปิดให้ทุกคนเข้าถึงได้)
    case 'admin/user/options':
        $optionController = new OptionController($connection);
        $optionController->getOptions();
        break;

    // 🌟 2. แอดมินกดปุ่มเพิ่มข้อมูล
    case 'admin/options/add':
        AuthMiddleware::checkAdmin($connection); // ล็อกสิทธิ์เฉพาะแอดมิน
        $optionController = new OptionController($connection);
        $optionController->addOption();
        break;

    // 🌟 3. แอดมินกดปุ่มถังขยะลบข้อมูล
    case 'admin/options/delete':
        AuthMiddleware::checkAdmin($connection); // ล็อกสิทธิ์เฉพาะแอดมิน
        $optionController = new OptionController($connection);
        $optionController->deleteOption();
        break;

    
    

   

    
    
    

    

        // 🌟 เพิ่มฟังก์ชันซิงค์ Google Calendar ตรงนี้ครับ
   
    
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
            
    

        
                
        

        
      
    
    // ------------------------------------------------
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
        // ตรวจสอบสิทธิ์ว่าล็อกอินหรือยัง และดึง ID ของพนักงาน
        $userData = AuthMiddleware::checkToken($connection);
        $currentUserId = is_array($userData) ? $userData['id'] : $userData->id;

        $transferModel = new VenTransferModel($connection);
        // สังเกตว่าเราส่ง $connection ไปให้ Controller ด้วยเพื่อให้ส่งต่อให้ updateGoogleCalendarDay ได้
        $transferController = new HomeController($transferModel, $connection);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $transferController->perform($currentUserId, $data);
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Method Not Allowed"]);
        }
        break;

    // 🌟 เพิ่มเคสใหม่ สำหรับยกเลิกใบเปลี่ยนเวร
    case 'ven/cancel_change':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['change_id'])) {
            $change_id = $data['change_id'];
            try {
                $connection->beginTransaction(); // 🌟 เริ่ม Transaction เพื่อความปลอดภัย

                // 1. ดึงข้อมูลว่าสลับเวรใครไปบ้าง (🌟 JOIN เอา ven_date มาด้วยเพื่อใช้อัปเดตปฏิทิน)
                $stmt = $connection->prepare("
                    SELECT vc.*, 
                           vs1.ven_date AS date1, 
                           vs2.ven_date AS date2 
                    FROM ven_change vc
                    LEFT JOIN ven_schedule vs1 ON vc.s1_id = vs1.id
                    LEFT JOIN ven_schedule vs2 ON vc.s2_id = vs2.id
                    WHERE vc.id = ?
                ");
                $stmt->execute([$change_id]);
                $changeReq = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($changeReq) {
                    $tableName = "ven_schedule";
                    
                    // 2. คืนค่าชื่อเดิมกลับมา และตั้งสถานะเป็น 1
                    if ($changeReq['is_swap'] == 1) {
                        // เอา user1_id กลับไปใส่ s1_id เหมือนเดิม
                        $stmt1 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 1 WHERE id = ?");
                        $stmt1->execute([$changeReq['user1_id'], $changeReq['s1_id']]);
                        
                        // เอา user2_id กลับไปใส่ s2_id เหมือนเดิม
                        $stmt2 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 1 WHERE id = ?");
                        $stmt2->execute([$changeReq['user2_id'], $changeReq['s2_id']]);
                    } else {
                        // เอา user1_id กลับไปใส่ s1_id เหมือนเดิม
                        $stmt1 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 1 WHERE id = ?");
                        $stmt1->execute([$changeReq['user1_id'], $changeReq['s1_id']]);
                    }

                    // 3. เปลี่ยนสถานะใบคำขอเป็น "ยกเลิกแล้ว" (หรือลบทิ้ง)
                    $stmtDel = $connection->prepare("DELETE FROM ven_change WHERE id = ?");
                    $stmtDel->execute([$change_id]);

                    $connection->commit(); // 🌟 บันทึกการเปลี่ยนแปลงในฐานข้อมูล

                    // 🌟 4. อัปเดต Google Calendar ให้กลับเป็นรายชื่อเดิม
                    if (function_exists('updateGoogleCalendarDay')) {
                        // อัปเดตวันที่ 1
                        if (!empty($changeReq['date1'])) {
                            updateGoogleCalendarDay($connection, $changeReq['date1']);
                        }
                        
                        // อัปเดตวันที่ 2 (ถ้าเป็นการสลับเวรและคนละวันกัน)
                        if ($changeReq['is_swap'] == 1 && !empty($changeReq['date2']) && $changeReq['date1'] != $changeReq['date2']) {
                            updateGoogleCalendarDay($connection, $changeReq['date2']);
                        }
                    }

                    echo json_encode(['success' => true, 'message' => 'ยกเลิกการเปลี่ยนเวรและอัปเดตปฏิทินเรียบร้อยแล้ว']);
                } else {
                    $connection->rollBack();
                    http_response_code(404); echo json_encode(['error' => 'ไม่พบข้อมูลใบเปลี่ยนเวร']);
                }
            } catch (Exception $e) {
                if ($connection->inTransaction()) {
                    $connection->rollBack(); // ถ้ายกเวรพัง ให้ Rollback ข้อมูลกลับ
                }
                http_response_code(500); echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400); echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
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
            
            // 4. ส่งให้ Controller จัดการ
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
            // 1. เช็คสิทธิ์ (เช่น ต้องเข้าระบบแล้ว หรือถ้าต้องการเฉพาะแอดมินให้ใช้ checkAdmin)
            AuthMiddleware::checkToken($connection); 

            // 2. เรียกใช้ Model และ Controller
            $financeModel = new FinanceModel($connection);
            $financeController = new FinanceController($financeModel);
            
            // 3. รับพารามิเตอร์และส่งให้ Controller
            $month = $_GET['month'] ?? date('Y-m');
            $command_id = $_GET['command_id'] ?? null;
            $financeController->getFinanceReport($month, $command_id);
            break;

        // โค้ดสำหรับดึงรายการคำสั่งส่งให้ Dropdown
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
        // ดึงค่าการตั้งค่าไปแสดงที่หน้าฟอร์ม
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