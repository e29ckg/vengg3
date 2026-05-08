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
require_once '../src/Controllers/FinanceController.php';
require_once '../src/Controllers/OptionController.php';
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
            // 2. ถ้ารหัสเดิมถูก ให้อัปเดตเป็นรหัสใหม่ (เข้ารหัสด้วย)
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

    // ใน switch ของ index.php
    // ==========================================
    // ⚙️ ดึงข้อมูลการตั้งค่าระบบ (สำหรับพนักงานทั่วไปใช้อ่านกฎ)
    // ==========================================
    case 'system_settings':
        //AuthMiddleware::checkToken($connection); // เช็คแค่ว่าล็อกอินแล้วก็พอ
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $settings = $settingModel->getSystemSettings();
            echo json_encode($settings ?: [
                'allow_swap' => 1,
                'advance_swap_days' => 3,
                'allow_retroactive_swap' => 0,
                'check_24h_consecutive' => 1
            ]);
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
            $result = $settingModel->toggleVenCommandStatus($data['id'], $data['status']);

            if ($result) {
                // 🌟 โค้ดที่ต้องเพิ่มเข้าไป (ถ้าสถานะคือ 2 = ยืนยันตารางแล้ว)
                if ($data['status'] == 1) { 
                    require_once '../src/Services/TelegramService.php';
                    $telegram = new TelegramService($connection);
                    
                    $msg = "📢 <b>ประกาศจากระบบจัดเวร</b>\n";
                    $msg .= "ตารางเวรประจำเดือนได้รับการยืนยันเรียบร้อยแล้ว!\n";
                    $msg .= "สมาชิกสามารถเข้าสู่ระบบเพื่อตรวจสอบ หรือส่งคำขอแลกเปลี่ยนเวรได้เลยครับ";
                    
                    // ส่ง 'notify_confirmed' ไปเช็คสวิตช์
                    $telegram->sendMessage($msg, 'notify_confirmed'); 
                }
                
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
            }

        }elseif ($action === 'update_status') {
            if ($settingModel->updateCommandStatus($data['id'], $data['status'])) {
                echo json_encode(["success" => true, "message" => "อัปเดตสถานะสำเร็จ"]);
            } else {
                http_response_code(500); 
                echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
            }
        }
        break;
    
    case 'admin/ven_com/delete':
        AuthMiddleware::checkDirector($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing command ID"]);
            exit;
        }
        echo json_encode(["success" => $settingModel->deleteVenCommand($_GET['id'])]);
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

    

    case 'admin/ven_approve':
        AuthMiddleware::checkAdmin($connection); 
        $action = $_GET['action'] ?? '';
        if ($action === 'list') {
                try {
                    // Query ดึงรายการคำขอเปลี่ยนเวร/สลับเวร พร้อม JOIN ข้อมูลที่เกี่ยวข้อง
                    $query = "SELECT 
                                vc.id, 
                                vc.change_no, 
                                vc.status, 
                                vc.is_swap, 
                                vc.created_at,
                                vc.user1_id, 
                                vc.user2_id,
                                -- ชื่อคนขอ (User A)
                                CONCAT_WS(' ', p1.prefix_name, p1.first_name, p1.last_name) AS user1_name,
                                -- ชื่อคนรับ/คนถูกสลับ (User B)
                                CONCAT_WS(' ', p2.prefix_name, p2.first_name, p2.last_name) AS user2_name,
                                -- ข้อมูลเวรที่ 1
                                vs1.ven_date AS s1_date, 
                                -- vs1.ven_time AS s1_time,
                                -- ข้อมูลเวรที่ 2 (กรณีสลับเวร)
                                vs2.ven_date AS s2_date, 
                                -- vs2.ven_time AS s2_time,
                                -- ข้อมูลหน้าที่
                                vns.name AS duty_role,
                                vn.name AS duty_main
                              FROM ven_change vc
                              LEFT JOIN profile p1 ON vc.user1_id = p1.user_id
                              LEFT JOIN profile p2 ON vc.user2_id = p2.user_id
                              LEFT JOIN ven_schedule vs1 ON vc.s1_id = vs1.id
                              LEFT JOIN ven_schedule vs2 ON vc.s2_id = vs2.id
                              LEFT JOIN ven_name_sub vns ON vs1.ven_name_sub_id = vns.id
                              LEFT JOIN ven_com vcom ON vs1.ven_com_id = vcom.id
                              LEFT JOIN ven_name vn ON vcom.ven_name_id = vn.id
                              -- เรียงลำดับ: เอาที่ยังไม่อนุมัติ (status=0) ขึ้นก่อน และตามด้วยวันที่ล่าสุด
                              ORDER BY vc.status ASC, vc.created_at DESC";
                    
                    $stmt = $connection->query($query);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    http_response_code(200);
                    echo json_encode($results);
                    exit;
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
                }
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            $change_id = $data['change_id'] ?? null;
            
            if (!$change_id) {
                http_response_code(400); echo json_encode(['error' => 'ไม่พบรหัสการเปลี่ยนเวร']); break;
            }
            
            try {
                // 1. ดึงข้อมูลใบคำขอเปลี่ยนเวร
                $stmt = $connection->prepare("SELECT * FROM ven_change WHERE id = ?");
                $stmt->execute([$change_id]);
                $changeReq = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($changeReq) {
                    $tableName = "ven_schedule"; // ตรวจสอบชื่อตารางให้ตรงกับของคุณ
                    
                    // 🌟 2. อัปเดตตารางเวร ให้สถานะกลับมาเป็น 1 (อนุมัติสมบูรณ์)
                    if ($changeReq['is_swap'] == 1) {
                        // อัปเดตกลับเป็น 1 ทั้งคู่
                        $stmt1 = $connection->prepare("UPDATE $tableName SET status = 1 WHERE id = ?");
                        $stmt1->execute([$changeReq['s1_id']]);
                        $stmt2 = $connection->prepare("UPDATE $tableName SET status = 1 WHERE id = ?");
                        $stmt2->execute([$changeReq['s2_id']]);
                    } else {
                        // อัปเดตกลับเป็น 1 แค่เวรเดียว
                        $stmt1 = $connection->prepare("UPDATE $tableName SET status = 1 WHERE id = ?");
                        $stmt1->execute([$changeReq['s1_id']]);
                    }
                    
                    // 3. เปลี่ยนสถานะใบคำขอเป็น "อนุมัติแล้ว"
                    $stmtUpdate = $connection->prepare("UPDATE ven_change SET status = 1 WHERE id = ?");
                    $stmtUpdate->execute([$change_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'อนุมัติการเปลี่ยนเวรเรียบร้อยแล้ว']);
                } else {
                    http_response_code(404); echo json_encode(['error' => 'ไม่พบข้อมูลใบคำขอนี้']);
                }
            } catch (PDOException $e) {
                http_response_code(500); echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
            }
            break;

    

   // ==========================================
        // 🌟 1. การตั้งค่าเวรหลัก และ หน้าที่ย่อย
        // ==========================================
        case 'admin/google_settings':
            AuthMiddleware::checkAdmin($connection);
            $action = $_GET['action'] ?? '';
            $data = json_decode(file_get_contents("php://input"), true);

            if ($action == 'get_google_config') {
                // 🌟 ดึงข้อมูลจากตารางใหม่ google_service_settings
                $stmt = $connection->prepare("SELECT setting_value FROM google_service_settings WHERE setting_key = 'google_service_account'");
                $stmt->execute();
                echo json_encode(['google_service_account' => $stmt->fetchColumn()]);
            } 
            elseif ($action == 'update_google_config') {
                // 🌟 อัปเดตข้อมูลในตารางใหม่ google_service_settings
                $stmt = $connection->prepare("UPDATE google_service_settings SET setting_value = ? WHERE setting_key = 'google_service_account'");
                $stmt->execute([$data['google_service_account']]);
                echo json_encode(['success' => true]);
            }
            break;
                
        case 'admin/setting':
            AuthMiddleware::checkAdmin($connection);
            require_once '../src/Models/Setting.php';
            $settingModel = new Setting($connection);
            
            $action = $_GET['action'] ?? '';
            $data = json_decode(file_get_contents("php://input"), true);

            if ($action == 'update_calendar_id') {
                $stmt = $connection->prepare("UPDATE ven_name SET google_calendar_id = ? WHERE id = ?");
                $stmt->execute([$data['google_calendar_id'], $data['id']]);
                echo json_encode(['success' => true]);
                exit;
            }

            if ($action == 'update_calendar_id') {
                $stmt = $connection->prepare("UPDATE ven_name SET google_calendar_id = ? WHERE id = ?");
                $stmt->execute([$data['google_calendar_id'], $data['id']]);
                echo json_encode(['success' => true]);
            }

            // 1.1 โหลดข้อมูลทั้งหมด (เวรหลัก + หน้าที่ย่อยซ้อนกัน)
            if ($action === 'ven_full') {
                echo json_encode([
                    "success" => true,
                    "data" => $settingModel->getVenFullData()
                ]);
            }
            elseif ($action === 'list_venname') {
                echo json_encode([
                    "success" => true,
                    "data" => $settingModel->getVenNames()
                ]);
            }
            // 1.2 ดึงข้อมูลเวรหลักตาม ID (ใช้ตอนกดแก้ไขเวรหลัก)
            elseif ($action === 'get_by_id') {
                echo json_encode($settingModel->getVenNameById($_GET['id']));
            }
            // 1.3 เพิ่ม/แก้ไข กลุ่มเวรหลัก
            elseif ($action === 'create_venname' || $action === 'update_venname') {
                echo json_encode(["success" => $settingModel->saveVenName($data)]);
            }
            // 1.4 ลบ กลุ่มเวรหลัก
            elseif ($action === 'delete_ven_name') {
                echo json_encode(["success" => $settingModel->deleteVenName($data['id'])]);
            }
            // 1.5 เพิ่ม/แก้ไข หน้าที่ย่อย
            elseif ($action === 'create_sub' || $action === 'update_sub') {
                echo json_encode(["success" => $settingModel->saveSubDuty($data)]);
            }
            // 1.6 ลบ หน้าที่ย่อย
            elseif ($action === 'delete_sub_duty') {
                echo json_encode(["success" => $settingModel->deleteSubDuty($data['id'])]);
            }
            // 1.7 บันทึกการจัดเรียงหน้าที่ย่อย (Drag & Drop)
            elseif ($action === 'update_order') {
                // $data ในที่นี้คือ array ของ {id, srt}
                echo json_encode(["success" => $settingModel->updateSubDutyOrder($data)]);
            }
            break;

        // ==========================================
        // 🌟 2. การจัดการผู้อยู่เวร (ผูกคนเข้ากับหน้าที่ย่อย)
        // ==========================================
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
            $change_id = $data['change_id'];
           try {
                // 1. ดึงข้อมูลว่าสลับเวรใครไปบ้าง
                $stmt = $connection->prepare("SELECT * FROM ven_change WHERE id = ?");
                $stmt->execute([$change_id]);
                $changeReq = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($changeReq) {
                    $tableName = "ven_schedule";
                    
                    // 🌟 2. คืนค่าชื่อเดิมกลับมา และตั้งสถานะเป็น 1
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

                    // 3. เปลี่ยนสถานะใบคำขอเป็น "ยกเลิกแล้ว" (สมมติใช้ status = 2 แทนการลบทิ้ง หรือจะใช้คำสั่ง DELETE ก็ได้)
                    $stmtDel = $connection->prepare("DELETE FROM ven_change WHERE id = ?");
                    $stmtDel->execute([$change_id]);

                    echo json_encode(['success' => true, 'message' => 'ยกเลิกการเปลี่ยนเวรเรียบร้อยแล้ว']);
                }
            }catch (PDOException $e) {
                http_response_code(500); echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'auth/me':
        require_once '../src/Controllers/AuthController.php';
        $authController = new AuthController($connection);
        $authController->getMe();
        break;
        
    

    case 'user/transfer':
            $userData = AuthMiddleware::checkToken($connection);
            $currentUserId = is_array($userData) ? $userData['id'] : $userData->id;
            
            $action = $_GET['action'] ?? '';
            
            if ($action === 'perform') {
                $data = json_decode(file_get_contents("php://input"), true);
                
                // รับค่าจาก Payload ของ Vue.js
                $s1_id = $data['schedule_id'] ?? null; // ID เวรตั้งต้น
                $user2_id = $data['new_user_id'] ?? null; // คนที่จะโอน/สลับด้วย
                $is_swap = isset($data['is_swap']) ? (int)$data['is_swap'] : 0;
                $s2_id = $data['s2_id'] ?? null; // ID เวรของคนที่จะสลับด้วย (ถ้ามี)
                
                if (!$s1_id || !$user2_id) {
                    http_response_code(400); echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']); break;
                }
                
                try {
                    $connection->beginTransaction(); // 🌟 เริ่ม Transaction ป้องกันข้อมูลพัง

                    // 1. เช็คว่าเวรนี้กำลังรออนุมัติเปลี่ยนเวรอยู่หรือไม่
                    $stmtCheck = $connection->prepare("SELECT id FROM ven_change WHERE (s1_id = ? OR s2_id = ?) AND status = 0");
                    $stmtCheck->execute([$s1_id, $s1_id]);
                    if ($stmtCheck->fetch()) {
                        $connection->rollBack();
                        http_response_code(400); echo json_encode(['error' => 'เวรนี้อยู่ระหว่างดำเนินการรออนุมัติอยู่แล้ว']); break;
                    }

                    // 2. รันเลขที่ใบเปลี่ยนเวร
                    $changeNo = "CH-" . date('Ym') . "-" . rand(1000, 9999);

                    // 3. บันทึกคำขอลงตาราง ven_change
                    $sql = "INSERT INTO ven_change (change_no, s1_id, user1_id, user2_id, is_swap, s2_id, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([ $changeNo, $s1_id, $currentUserId, $user2_id, $is_swap, ($is_swap == 1) ? $s2_id : null ]);

                    // 🌟 4. ย้ายชื่อในตารางเวร และปรับสถานะเป็น 2 ทันที!
                    $tableName = "ven_schedule"; // ตรวจสอบชื่อตารางให้ตรงกับของคุณ
                    
                    if ($is_swap == 1) {
                        // กรณีสลับเวร (เปลี่ยนชื่อทั้ง 2 วัน และปรับสถานะ=2)
                        $stmt1 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                        $stmt1->execute([$user2_id, $s1_id]);
                        
                        $stmt2 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                        $stmt2->execute([$currentUserId, $s2_id]);
                    } else {
                        // กรณียกให้ (โอนขาด) (เปลี่ยนชื่อแค่เวรเดียว และปรับสถานะ=2)
                        $stmt1 = $connection->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                        $stmt1->execute([$user2_id, $s1_id]);
                    }

                    $connection->commit(); // 🌟 บันทึก Transaction

                    echo json_encode([ 'success' => true, 'message' => 'บันทึกคำขอเปลี่ยนเวรสำเร็จ', 'change_no' => $changeNo ]);
                    
                } catch (PDOException $e) {
                    $connection->rollBack();
                    http_response_code(500); echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
                }
            }
            break;



    case 'report/monthly':
        // ตรวจสอบ Token ก่อนเข้าถึง API
        $currentUser = AuthMiddleware::checkToken($connection);
        
        if (isset($_GET['month']) && isset($_GET['year'])) {            
            require_once '../src/Controllers/ReportController.php';
            $creportController = new ReportController($connection);
            $month = $_GET['month'] ? $_GET['month'] : null;
            $year = $_GET['year'] ? $_GET['year'] : null;
            $monthYear = $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT);
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

    // โค้ดสำหรับดึงข้อมูลตารางรายงานการเงิน
    case 'finance/report':
        $financeController = new FinanceController($connection); // หรือ $db ตามที่คุณตั้งชื่อตัวแปรเชื่อมต่อ
        $month = $_GET['month'] ?? date('Y-m');
        $command_id = $_GET['command_id'] ?? null;
        
        $financeController->getFinanceReport($month, $command_id);
        break;

    // โค้ดสำหรับดึงรายการคำสั่งส่งให้ Dropdown
    case 'get_commands':
        $financeController = new FinanceController($connection);
        $month = $_GET['month'] ?? date('Y-m');
        
        $financeController->getCommands($month);
        break;
    
    // ==========================================
    // 📢 ระบบตั้งค่าการแจ้งเตือน Telegram
    // ==========================================
    
    // 1. API ดึงข้อมูลการตั้งค่ามาแสดงที่หน้าฟอร์ม
    case 'admin/telegram_settings':
            AuthMiddleware::checkAdmin($connection); // ตรวจสอบสิทธิ์แอดมิน
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                require_once '../src/Models/Setting.php';
                $settingModel = new Setting($connection);
                $settings = $settingModel->getTelegramSettings();
                
                // หากยังไม่มีข้อมูลในตาราง ให้ส่งค่าตั้งต้นกลับไป
                if (!$settings) {
                    $settings = [
                        'bot_token' => '', 'chat_id' => '',
                        'notify_confirmed' => true, 
                        'notify_change_request' => true, 
                        'notify_approval' => true,
                        'notify_times' => [] // 🌟 เตรียม Array ว่างไว้
                    ];
                } else {
                    // แปลงค่า 1/0 กลับเป็น Boolean ให้ Vue.js เข้าใจง่าย
                    $settings['notify_confirmed'] = (bool)$settings['notify_confirmed'];
                    $settings['notify_change_request'] = (bool)$settings['notify_change_request'];
                    $settings['notify_approval'] = (bool)$settings['notify_approval'];
                    
                    // 🌟 ดึงข้อมูลเวลาแจ้งเตือนจาก DB มาแนบไปด้วย
                    try {
                        $stmtTime = $connection->prepare("SELECT send_time, notify_day, status FROM telegram_notify_times ORDER BY send_time ASC");
                        $stmtTime->execute();
                        $notifyTimes = $stmtTime->fetchAll(PDO::FETCH_ASSOC);
                        
                        // แปลงชนิดข้อมูลให้ Vue.js เข้าใจง่าย
                        foreach ($notifyTimes as &$time) {
                            $time['status'] = (bool)$time['status'];
                            $time['notify_day'] = (int)$time['notify_day']; // ให้เป็น 0 หรือ 1
                        }
                        
                        $settings['notify_times'] = $notifyTimes; // นำไปใส่ใน Array หลัก
                        
                    } catch (PDOException $e) {
                        $settings['notify_times'] = [];
                    }
                }
                
                echo json_encode($settings);
            }
            break;

    // 2. API บันทึกการตั้งค่าลง Database
    case 'admin/telegram_settings/update':
        AuthMiddleware::checkAdmin($connection); // ตรวจสอบสิทธิ์แอดมิน
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. รับค่าและแปลงเป็น Array (บังคับใส่ true)
            $data = json_decode(file_get_contents("php://input"), true);

            require_once '../src/Models/Setting.php'; 
            $settingModel = new Setting($connection);
            
            // 3. ส่งข้อมูลไปอัปเดตที่ Model
            $result = $settingModel->updateTelegramSettings($data);
            
            if ($result) {
                echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
            }
        }
        break;

    // 3. API ทดสอบส่งข้อความ (ใช้ค่าจากฟอร์มชั่วคราว ไม่ต้องรอเซฟลงฐานข้อมูลก่อน)
    case 'admin/telegram_settings/test':
        AuthMiddleware::checkAdmin($connection);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            $botToken = $data['bot_token'];
            $chatId = $data['chat_id'];
            
            if(empty($botToken) || empty($chatId)) {
                http_response_code(400);
                echo json_encode(["error" => "กรุณากรอก Token และ Chat ID ก่อนทดสอบ"]);
                exit;
            }

            $message = "✅ <b>ทดสอบระบบแจ้งเตือน</b>\nข้อความนี้ถูกส่งจาก \"ระบบจัดเวรนอกเวลาทำการ\" เพื่อทดสอบการเชื่อมต่อ Telegram ครับ";
            
            // ยิง API ไปหา Telegram โดยตรง
            $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
            $postData = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                echo json_encode(["success" => true, "message" => "ส่งข้อความสำเร็จ!"]);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ส่งไม่สำเร็จ กรุณาตรวจสอบ Token และ Chat ID"]);
            }
        }
        break;

    // 4. API ส่งแจ้งเตือนเวรของวันนี้แบบ Manual
   case 'admin/telegram_settings/manual_notify':
        AuthMiddleware::checkAdmin($connection); // ตรวจสอบสิทธิ์
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once '../src/Services/TelegramService.php';
            $telegram = new TelegramService($connection);

            // 🌟 1. รับค่าจาก Vue Frontend (day_offset)
            $data = json_decode(file_get_contents("php://input"), true);
            $day_offset = isset($data['day_offset']) ? (int)$data['day_offset'] : 0;

            // 🌟 2. กำหนดวันที่เป้าหมายและข้อความตาม day_offset
            if ($day_offset === 1) {
                $target_date = date('Y-m-d', strtotime('+1 day')); // วันพรุ่งนี้
                $display_date = date('d/m/Y', strtotime('+1 day'));
                $day_text = "วันพรุ่งนี้";
            } else {
                $target_date = date('Y-m-d'); // วันนี้
                $display_date = date('d/m/Y');
                $day_text = "วันนี้";
            }

            // 🌟 3. ดึงข้อมูลเวรตามวันที่เป้าหมาย
            $sql = "SELECT vs.*, p.prefix_name, p.first_name as staff_name, p.last_name, vn.name as duty_name 
                    FROM ven_schedule vs 
                    LEFT JOIN profile p ON vs.user_id = p.user_id 
                    LEFT JOIN ven_com vc On vc.id = vs.ven_com_id 
                    LEFT JOIN ven_name_sub vns ON vns.id = vs.ven_name_sub_id 
                    LEFT JOIN ven_name vn ON vn.id = vns.ven_name_id 
                    WHERE DATE(vs.ven_date) = :target_date
                    ORDER BY vn.srt ASC, vns.srt ASC;";

            $stmt = $connection->prepare($sql);
            $stmt->execute([':target_date' => $target_date]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($schedules) > 0) {
                // 🌟 4. ปรับข้อความหัวจดหมายให้ตรงกับวันที่
                $msg = "📢 <b>แจ้งเตือนเวรปฏิบัติงาน ($day_text)</b>\n";
                $msg .= "📅 วันที่: " . $display_date . "\n";
                $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
                
                $currentDuty = '';
                foreach ($schedules as $row) {
                    if ($currentDuty != $row['duty_name']) {
                        $msg .= "📌 <b>" . $row['duty_name'] . "</b>\n";
                        $currentDuty = $row['duty_name'];
                    }
                    $msg .= "   👤 " . $row['prefix_name'] . $row['staff_name'] . " " . $row['last_name'] . "\n";
                }
                
                $msg .= "➖➖➖➖➖➖➖➖➖➖\n";
                $msg .= "🙏 โปรดเตรียมความพร้อมในการปฏิบัติหน้าที่ครับ";

                // สั่งส่งข้อความ
                $result = $telegram->sendMessage($msg);
                
                if ($result) {
                    echo json_encode(["success" => true, "message" => "ส่งแจ้งเตือนเวรของ{$day_text}เข้ากลุ่มสำเร็จ!"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "ส่งไม่สำเร็จ โปรดตรวจสอบ Token และ Chat ID"]);
                }
            } else {
                http_response_code(404);
                // 🌟 5. แจ้งเตือนกลับกรณีไม่มีเวร
                echo json_encode(["error" => "ไม่มีผู้ปฏิบัติหน้าที่ในตารางเวรของ{$day_text}ครับ"]);
            }
        }
        break;

    // ==========================================
    // ⚙️ ตั้งค่าระบบ (System Settings)
    // ==========================================
    case 'admin/system_settings':
        AuthMiddleware::checkAdmin($connection);
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $settings = $settingModel->getSystemSettings();
            if (!$settings) {
                $settings = [
                    'system_name' => 'ระบบบริหารจัดการเวรนอกเวลาทำการ', 
                    'allow_swap' => 1, 
                    'advance_swap_days' => 3, 
                    'allow_retroactive_swap' => 0, // 🌟 เพิ่มใหม่
                    'check_24h_consecutive' => 1,    // 🌟 เพิ่มใหม่
                    'maintenance_mode' => 0
                ];
            }
            echo json_encode($settings);
        } 
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $result = $settingModel->updateSystemSettings($data);
            
            if ($result) {
                echo json_encode(["success" => true, "message" => "อัปเดตการตั้งค่าระบบสำเร็จ"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
            }
        }
        break;

    // ==========================================
        // 🌟 ตั้งค่าหน่วยงานและผู้ลงนาม (Agency Settings)
        // ==========================================
        case 'admin/agency_settings':
            require_once '../src/Models/Setting.php';
            $settingModel = new Setting($connection);

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                AuthMiddleware::checkToken($connection); 
                echo json_encode($settingModel->getAgencySettings());
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                AuthMiddleware::checkAdmin($connection);
                $data = json_decode(file_get_contents("php://input"), true);
                if ($settingModel->updateAgencySettings($data)) {
                    echo json_encode(["success" => true, "message" => "บันทึกข้อมูลสำเร็จ"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลตั้งค่าหน่วยงานได้"]);
                }
            }
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
            require_once '../src/Models/Setting.php';
            $settingModel = new Setting($connection);
            echo json_encode($settingModel->getUserChangeHistory($user_id));
            break;

        

    case 'admin/settings/update_toggle':
        AuthMiddleware::checkAdmin($connection); // ตรวจสอบว่าเป็น Admin หรือไม่
        $data = json_decode(file_get_contents("php://input"), true);
        
        require_once '../src/Models/Setting.php';
        $settingModel = new Setting($connection);
        
        // อัปเดตฟิลด์ในตาราง system_settings (สมมติว่าตารางมีแถวเดียว id=1)
        if ($settingModel->updateSystemSetting($data['setting_key'], $data['setting_value'])) {
            echo json_encode(["success" => true]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update"]);
        }
        break;

    // 🌟 อัปโหลดรูปโปรไฟล์
        case 'user/profile/upload_avatar':
            $userData = AuthMiddleware::checkToken($connection);
            $userId = is_array($userData) ? $userData['id'] : $userData->id;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                // สร้างโฟลเดอร์ถ้ายังไม่มี
                $uploadDir = __DIR__ . '/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // ตรวจสอบนามสกุลไฟล์
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    
                    // 🌟 1. ดึงชื่อไฟล์รูปเดิมจากฐานข้อมูลก่อน
                    $stmtOld = $connection->prepare("SELECT avatar FROM profile WHERE user_id = ?");
                    $stmtOld->execute([$userId]);
                    $oldAvatar = $stmtOld->fetchColumn();

                    // ตั้งชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
                    $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
                    $dest_path = $uploadDir . $newFileName;

                    // ทำการย้ายไฟล์ใหม่เข้าไปเก็บ
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        
                        // 🌟 2. ถ้าย้ายไฟล์ใหม่สำเร็จ และมีไฟล์รูปเดิมอยู่ ให้ลบรูปเดิมทิ้ง
                        if (!empty($oldAvatar) && file_exists($uploadDir . $oldAvatar)) {
                            unlink($uploadDir . $oldAvatar);
                        }

                        // อัปเดตชื่อไฟล์ลงฐานข้อมูล
                        $stmt = $connection->prepare("UPDATE profile SET avatar = ? WHERE user_id = ?");
                        $stmt->execute([$newFileName, $userId]);
                        
                        echo json_encode(["success" => true, "avatar" => $newFileName]);
                        break;
                    }
                } else {
                    http_response_code(400); 
                    echo json_encode(["error" => "รองรับเฉพาะไฟล์รูปภาพ (jpg, png, gif) เท่านั้น"]); 
                    break;
                }
            }
            http_response_code(400); 
            echo json_encode(["error" => "ไม่สามารถอัปโหลดไฟล์ได้"]);
            break;
        
        // 🌟 สำรองข้อมูล (เฉพาะรูปภาพ .zip)
        case 'admin/backup/images':
            AuthMiddleware::checkAdmin($connection); // ล็อกเฉพาะแอดมิน

            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '300');

            $backupDir = __DIR__ . '/uploads/backups/';
            if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

            $date = date('Ymd_His');
            $zipFileName = "images_backup_{$date}.zip";
            $zipFilePath = $backupDir . $zipFileName;
            $zip = new ZipArchive();
            
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                
                $imagesDir = __DIR__ . '/uploads/avatars/';
                $hasFiles = false; // ตัวแปรเช็คว่ามีรูปไหม

                if (is_dir($imagesDir)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imagesDir), RecursiveIteratorIterator::LEAVES_ONLY);
                    foreach ($files as $name => $file) {
                        // เช็คว่าไม่ใช่โฟลเดอร์เปล่า (. และ ..)
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = 'avatars/' . substr($filePath, strlen($imagesDir));
                            $zip->addFile($filePath, $relativePath);
                            $hasFiles = true;
                        }
                    }
                }

                // 🌟 ป้องกัน Error กรณีโฟลเดอร์รูปภาพยังไม่มีไฟล์เลย
                if (!$hasFiles) {
                    $zip->addFromString('empty_note.txt', 'ยังไม่มีรูปภาพในระบบ');
                }

                $zip->close();
            }

            // 🌟 สำคัญที่สุด: ล้าง Buffer (ข้อมูลขยะ/ช่องว่าง) ก่อนส่งไฟล์ ZIP
            if (ob_get_length()) {
                ob_end_clean();
            }

            // สั่งให้ Browser ดาวน์โหลดไฟล์ ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            header('Cache-Control: max-age=0'); // ป้องกัน Browser จำไฟล์เดิม
            
            readfile($zipFilePath);

            // เมื่อดาวน์โหลดเสร็จ ให้ลบไฟล์ ZIP ใน Server ทิ้ง
            unlink($zipFilePath);
            
            // 🌟 หยุดการทำงานทันที ป้องกันไม่ให้โค้ดส่วนอื่นแอบส่งข้อความตามหลัง
            exit;

    // 🌟 สำรองข้อมูล (เฉพาะฐานข้อมูล .sql)
        case 'admin/backup/sql':
            AuthMiddleware::checkAdmin($connection); // ล็อกเฉพาะแอดมิน

            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '300');

            $date = date('Ymd_His');
            $sqlFileName = "database_{$date}.sql";

            // สั่งให้ Browser ดาวน์โหลดไฟล์เป็น .sql ทันที โดยไม่ต้องเซฟลง Server ก่อน
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $sqlFileName . '"');

            $tables = [];
            $stmt = $connection->query('SHOW TABLES');
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            echo "-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $stmt = $connection->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                echo "\nDROP TABLE IF EXISTS `$table`;\n";
                echo $row[1] . ";\n\n";

                $stmt = $connection->query("SELECT * FROM `$table`");
                $numCols = $stmt->columnCount();

                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    echo "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $numCols; $j++) {
                        if ($row[$j] === null) {
                            echo "NULL";
                        } else {
                            echo $connection->quote($row[$j]);
                        }
                        if ($j < ($numCols - 1)) {
                            echo ",";
                        }
                    }
                    echo ");\n";
                }
                echo "\n";
            }
            break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}

?>