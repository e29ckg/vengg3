<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../src/Models/Setting.php';
require_once '../src/config/database.php';
require_once '../src/Controllers/VenController.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/UserController.php';
require_once '../src/Controllers/SettingController.php';
require_once '../src/Middleware/AuthMiddleware.php';

$database = new Database();
$connection = $database->getConnection();
$settingModel = new Setting($connection);

$route = $_GET['route'] ?? '';
$action = $_GET['action'] ?? '';
$table = $_GET['table'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

switch ($route) {

    // ==========================================
    // 🌟 ระบบตั้งค่าเวร (เวรหลัก / หน้าที่ย่อย)
    // ==========================================
    case 'admin/setting':
        // AuthMiddleware::checkAdmin($connection); // เปิดใช้ถ้ามีระบบ Login

        if ($action === 'ven_full') {
            // ดึงข้อมูลเวรหลักพร้อมหน้าที่ย่อย (ใช้ในหน้าตั้งค่า และ หน้าจัดเวร)
            echo json_encode($settingModel->getVenFullData());
            break;
        }

        // จัดการเวรหลัก (ven_name)
        if ($table === 'ven_name') {
            if ($action === 'list') {
                echo json_encode($settingModel->getVenNames());
            } elseif ($action === 'create') {
                echo json_encode(["success" => $settingModel->createVenName($data)]);
            } elseif ($action === 'update') {
                echo json_encode(["success" => $settingModel->updateVenName($data)]);
            } elseif ($action === 'delete') {
                echo json_encode(["success" => $settingModel->deleteVenName($data['id'])]);
            }
        } 
        // จัดการหน้าที่ย่อย (ven_name_sub)
        elseif ($table === 'ven_name_sub') {
            if ($action === 'create') {
                echo json_encode(["success" => $settingModel->createVenNameSub($data)]);
            } elseif ($action === 'update') {
                echo json_encode(["success" => $settingModel->updateVenNameSub($data)]);
            } elseif ($action === 'delete') {
                echo json_encode(["success" => $settingModel->deleteVenNameSub($data['id'])]);
            } 
            // 🌟 เพิ่มเงื่อนไขสำหรับอัปเดตการจัดเรียง (Drag & Drop)
            elseif ($action === 'update_order') {
                if ($settingModel->updateSubDutyOrder($data)) {
                    echo json_encode(["success" => true]);
                } else {
                    http_response_code(500); 
                    echo json_encode(["error" => "Failed to update order"]);
                }
            }
        }
        break;

    // ==========================================
    // 🌟 ระบบคำสั่งเวร (Ven Command)
    // ==========================================
    case 'admin/ven_com':
        if ($action === 'list') {
            $query = "SELECT c.*, n.name as ven_name_title, n.dn 
                      FROM ven_com c 
                      JOIN ven_name n ON c.ven_name_id = n.id 
                      ORDER BY c.ven_month DESC, c.com_num DESC";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } elseif ($action === 'create') {
            echo json_encode(["success" => $settingModel->createVenCommand($data)]);
        } elseif ($action === 'update') {
            echo json_encode(["success" => $settingModel->updateVenCommand($data)]);
        } elseif ($action === 'delete') {
            echo json_encode(["success" => $settingModel->deleteVenCommand($data['id'])]);
        }
        break;

    // ==========================================
    // 🌟 ระบบตารางเวรปฏิทิน (Ven Schedule)
    // ==========================================
    case 'admin/ven_schedule':
        if ($action === 'list_month') {
            echo json_encode($settingModel->getSchedulesByMonth($_GET['month']));
        } elseif ($action === 'add') {
            if ($settingModel->addSchedule($data)) {
                echo json_encode(["success" => true, "message" => "บันทึกเวรสำเร็จ"]);
            } else {
                http_response_code(500); 
                echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
            }
        } elseif ($action === 'remove') {
            if ($settingModel->removeSchedule($data['id'])) {
                echo json_encode(["success" => true, "message" => "ลบเวรสำเร็จ"]);
            } else {
                http_response_code(500); 
                echo json_encode(["error" => "ไม่สามารถลบข้อมูลได้"]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Route not found"]);
        break;
}
?>