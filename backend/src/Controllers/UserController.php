<?php
// backend/src/Controllers/UserController.php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Services/AvatarUploadService.php';

class UserController {
    
    private $userModel;

    public function __construct($userModel) {
        $this->userModel = $userModel;
    }

    public function listUsers() {
        $stmt = $this->userModel->getAllUsers();
        
        $users_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ประกอบร่างชื่อเต็มให้สวยงาม สำหรับแสดงในตารางหลัก
            $fullName = $row['prefix_name'] . $row['first_name'] . ' ' . $row['last_name'];
            if (trim($fullName) === '') {
                $fullName = '-';
            }

            array_push($users_arr, [
                "id" => $row['id'],
                "username" => $row['username'],
                "full_name" => $fullName,
                "role" => $row['role'],
                "status" => $row['status'],
                "prefix_name" => $row['prefix_name'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "position" => $row['position'],
                "srt" => $row['srt'],
                "department" => $row['department'],
                "phone" => $row['phone'],
                "bank_account" => $row['bank_account'],
                "bank_comment" => $row['bank_comment'],
                "st" => $row['st']
            ]);
        }

        http_response_code(200);
        echo json_encode($users_arr);
    }

    public function listUsersForVenUser() {
        $stmt = $this->userModel->getAllUsersForVenUser();
        
        $users_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ประกอบร่างชื่อเต็มให้สวยงาม สำหรับแสดงในตารางหลัก
            $fullName = $row['prefix_name'] . $row['first_name'] . ' ' . $row['last_name'];
            if (trim($fullName) === '') {
                $fullName = '-';
            }

            array_push($users_arr, [
                "id" => $row['id'],
                "username" => $row['username'],
                "full_name" => $fullName,
                "role" => $row['role'],
                "status" => $row['status'],
                "prefix_name" => $row['prefix_name'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "position" => $row['position'],
                "srt" => $row['srt'],
                "department" => $row['department'],
                "st" => $row['st']
            ]);
        }

        http_response_code(200);
        echo json_encode($users_arr);
    }
    public function listUserDirectory(): void {
        $rows=[];
        $query=$this->userModel->getAllUsers();
        while ($row=$query->fetch(PDO::FETCH_ASSOC)) {
            $rows[]=['id'=>$row['id'],'full_name'=>trim(($row['prefix_name'] ?? '').($row['first_name'] ?? '').' '.($row['last_name'] ?? '')),
                'position'=>$row['position'],'department'=>$row['department'],'status'=>$row['status']];
        }
        echo json_encode($rows);
    }

    // รับข้อมูลสร้างผู้ใช้ใหม่
    public function createUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // ตรวจสอบว่าส่งข้อมูลสำคัญมาครบไหม
        if (empty($data['username']) || empty($data['password']) || empty($data['first_name'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน"]);
            return;
        }

        $result = $this->userModel->createUser($data);

        if ($result['success']) {
            http_response_code(201); // 201 Created
            echo json_encode(["message" => $result['message']]);
        } else {
            http_response_code(400); // 400 Bad Request
            echo json_encode(["error" => $result['message']]);
        }
    }

   // รับคำสั่งเปลี่ยนสถานะ
    public function changeStatus() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // เช็คว่ามีการส่ง ID และ Status ใหม่มาไหม
        if (empty($data['id']) || !in_array((string)($data['status'] ?? ''), ['0','10'], true)) {
            http_response_code(400);
            echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
            return;
        }
        
        // 1. ทำการเปลี่ยนสถานะการเข้าใช้งาน
        if ($this->userModel->toggleStatus($data['id'], $data['status'])) {
            
            // 🌟 2. ถ้าสถานะถูกปรับเป็น 0 (ระงับ) ให้สั่งปรับ srt = 999 ด้วย
            if ($data['status'] == 0) {
                $this->userModel->setLowestSeniority($data['id']);
            }
            
            http_response_code(200);
            echo json_encode(["message" => "อัปเดตสถานะสำเร็จ"]);
        } else {
            http_response_code(409);
            echo json_encode(["error" => "ต้องคงแอดมินที่เปิดใช้งานอย่างน้อยหนึ่งบัญชี"]);
        }
    }

    // รับข้อมูลอัปเดตผู้ใช้งาน
    public function updateUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['first_name']) || empty($data['role'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน"]);
            return;
        }

        $result = $this->userModel->updateUser($data);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(["message" => $result['message']]);
        } else {
            http_response_code($result['code'] ?? 500);
            echo json_encode(["error" => $result['message']]);
        }
    }   

    public function deleteUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "กรุณาระบุ ID ของผู้ใช้ที่ต้องการลบ"]);
            return;
        }

        if ($this->userModel->deleteUser($data['id'])) {
            http_response_code(200);
            echo json_encode(["message" => "ลบผู้ใช้สำเร็จ"]);
        } else {
            http_response_code(409);
            echo json_encode(["error" => "ต้องคงแอดมินที่เปิดใช้งานอย่างน้อยหนึ่งบัญชี"]);
        }
    }  
    
    public function update_order() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(["error" => "data not format"]);
            return;
        }

        if ($this->userModel->update_order($data)) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "สำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "เกิดข้อผิดพลาด ไม่สามารถเรียงลำดับผู้ใช้ได้"]);
        }        
    }  

    public function uploadAvatar($userId, $file, $baseDir) {
        try {
            if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) ||
                !is_string($userId) || !preg_match('/\A[A-Za-z0-9-]{1,36}\z/', $userId)) {
                throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ');
            }
            $extension = AvatarUploadService::validate($file['tmp_name'], $file['name']);
            $uploadDir = $baseDir . '/uploads/avatars/';
            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) throw new RuntimeException('บันทึกรูปไม่ได้');
            $oldAvatar = $this->userModel->getAvatar($userId);
            $newFileName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
            $path = $uploadDir . $newFileName;
            if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('บันทึกรูปไม่ได้');
            if (!$this->userModel->updateAvatar($userId, $newFileName)) {
                @unlink($path);
                throw new RuntimeException('บันทึกข้อมูลรูปไม่ได้');
            }
            if (AvatarUploadService::safeStoredName($oldAvatar) && is_file($uploadDir . $oldAvatar)) @unlink($uploadDir . $oldAvatar);
            echo json_encode(['success' => true, 'avatar' => $newFileName]);
        } catch (RuntimeException $error) {
            http_response_code(400);
            echo json_encode(['error' => 'อัปโหลดรูปไม่สำเร็จ ใช้รูปจริงขนาดไม่เกิน 2 MB และด้านละไม่เกิน 4096 pixels']);
        }
    }

    // 🌟 ส่งข้อมูลโปรไฟล์กลับไปให้ Vue.js
    public function getProfile($userId) {
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ไม่พบข้อมูลผู้ใช้งาน']);
            return;
        }

        // เรียกใช้ Model แทนการเขียน SQL โดยตรง
        $profile = $this->userModel->getProfile($userId);

        if ($profile) {
            echo json_encode($profile);
        } else {
            // ป้องกัน Error กรณีไม่มีข้อมูลในตาราง profile ให้ส่งค่าว่างกลับไป
            echo json_encode([
                'avatar' => null, 'prefix_name' => '', 'first_name' => '', 
                'last_name' => '', 'position' => '', 'department' => '', 
                'phone' => '', 'bank_account' => '', 'bank_comment' => ''
            ]);
        }
    }

    public function updateProfile($userId) {
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ไม่พบข้อมูลผู้ใช้งาน']);
            return;
        }

        // รับข้อมูล JSON ที่ส่งมาจากหน้าเว็บ
        $data = json_decode(file_get_contents("php://input"), true);

        // ส่งให้ Model ทำการบันทึกลงฐานข้อมูล
        if ($this->userModel->updateProfile($userId, $data)) {
            echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลโปรไฟล์สำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "ไม่สามารถอัปเดตข้อมูลได้"]);
        }
    }
}
?>
