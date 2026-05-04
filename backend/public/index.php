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
    // ==========================================
    // ⚙️ ดึงข้อมูลการตั้งค่าระบบ (สำหรับพนักงานทั่วไปใช้อ่านกฎ)
    // ==========================================
    case 'system_settings':
        AuthMiddleware::checkToken($connection); // เช็คแค่ว่าล็อกอินแล้วก็พอ
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
                $result = $settingModel->updateChangeStatus($data['id'], $data['status']);

                if ($result) {
                    // 🌟 โค้ดที่ต้องเพิ่มเข้าไป (ดักเฉพาะตอนอนุมัติสำเร็จ)
                    if ($data['status'] == 1) {
                        require_once '../src/Services/TelegramService.php';
                        $telegram = new TelegramService($connection);
                        
                        $msg = "✅ <b>แจ้งผลการอนุมัติแลกเวร</b>\n";
                        $msg .= "ผู้บริหารได้ <b>อนุมัติ</b> คำขอแลกเปลี่ยนเวร ". $data['change_no'] ." เรียบร้อยแล้ว!\n";
                        $msg .= "ระบบได้ทำการอัปเดตตารางเวรให้โดยอัตโนมัติครับ";
                        
                        // ส่ง 'notify_approval' ไปเช็คสวิตช์
                        $telegram->sendMessage($msg, 'notify_approval');
                    }
                    http_response_code(200);
                    echo json_encode(["success" => true, "message" => "อัปเดตสถานะเรียบร้อย"]);
                }else {
                    http_response_code(500);
                    echo json_encode(["error" => "ไม่สามารถอัปเดตสถานะได้"]);
                }
            }
        }
        break;

   // ==========================================
        // 🌟 1. การตั้งค่าเวรหลัก และ หน้าที่ย่อย
        // ==========================================
        case 'admin/setting':
            AuthMiddleware::checkAdmin($connection);
            require_once '../src/Models/Setting.php';
            $settingModel = new Setting($connection);
            
            $action = $_GET['action'] ?? '';
            $data = json_decode(file_get_contents("php://input"), true);

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

            $result = $settingModel->transferShift($data['schedule_id'], $data['new_user_id']);

            if ($result) {
                // 🌟 โค้ดที่ต้องเพิ่มเข้าไป
                require_once '../src/Services/TelegramService.php';
                $telegram = new TelegramService($connection);
                
                $msg = "🔄 <b>มีการส่งคำขอแลกเปลี่ยนเวรใหม่</b>\n";
                $msg .= "โปรดเข้าสู่ระบบเพื่อตรวจสอบรายละเอียดและดำเนินการครับ";
                
                /* 💡 Tips: ถ้าคุณดึงชื่อคนขอ หรือวันที่ขอ มาได้ สามารถนำมาต่อ String เพิ่มความสวยงามได้ครับ
                เช่น: $msg .= "\nจาก: คุณ " . $data['user1_name'];
                */
                
                // ส่ง 'notify_change_request' ไปเช็คสวิตช์
                $telegram->sendMessage($msg, 'notify_change_request');
                
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "โอนเวรเรียบร้อย"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "ไม่สามารถส่งคำขอแลกเปลี่ยนได้ เกิดข้อผิดพลาดที่ฐานข้อมูล"]);
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

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
        break;
}

?>