<?php
// backend/src/Models/User.php

class User {
    private $conn;
    private $table_name = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ฟังก์ชันตรวจสอบการล็อกอิน
    public function login($username, $password) {
        // ดึงข้อมูลผู้ใช้จากชื่อผู้ใช้
        $query = "SELECT id, username, password_hash, role, status FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // เช็คว่าสถานะผู้ใช้ถูกระงับหรือไม่ (สมมติ 10 = ปกติ)
            if ($row['status'] != 10) {
                return ["success" => false, "message" => "Account is disabled."];
            }

            // ตรวจสอบรหัสผ่านที่ส่งมา เทียบกับ Hash ในฐานข้อมูล
            if (password_verify($password, $row['password_hash'])) {
                // รหัสผ่านถูกต้อง สร้าง Token 32 ตัวอักษร
                $token = bin2hex(random_bytes(16));
                
                // บันทึก Token ลงในช่อง auth_key
                $this->updateAuthKey($row['id'], $token);

                // ส่งข้อมูลผู้ใช้และ Token กลับไป
                return [
                    "success" => true,
                    "user" => [
                        "id" => $row['id'],
                        "username" => $row['username'],
                        "role" => $row['role'],
                        "token" => $token
                    ]
                ];
            } else {
                return ["success" => false, "message" => "Invalid password."];
            }
        }
        
        return ["success" => false, "message" => "User not found."];
    }

    // ฟังก์ชันอัปเดต Token ลงฐานข้อมูล
    private function updateAuthKey($id, $token) {
        $query = "UPDATE " . $this->table_name . " SET auth_key = :token WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    // ฟังก์ชันตรวจสอบ Token ว่าถูกต้องและมีอยู่จริงหรือไม่ (สำหรับ Middleware)
    public function validateToken($token) {
        $query = "SELECT id, username, role FROM " . $this->table_name . " WHERE auth_key = :token AND status = 10 LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // ถ้า Token ถูกต้อง ส่งข้อมูลผู้ใช้ (id, username, role) กลับไปให้ระบบรับรู้
            return $stmt->fetch(PDO::FETCH_ASSOC); 
        }
        
        return false; // Token ปลอม หรือถูกระงับ
    }

    // ฟังก์ชันดึงรายชื่อผู้ใช้ทั้งหมดสำหรับ Admin
    // ฟังก์ชันดึงรายชื่อผู้ใช้ทั้งหมด (อัปเดตให้ดึงข้อมูลครบทุกฟิลด์)
    public function getAllUsers() {
        // ใช้ LEFT JOIN ดึง fname มาเพื่อเอาคำนำหน้าไปต่อกับชื่อ
        $query = "SELECT 
                    u.id, 
                    u.username, 
                    u.role, 
                    u.status,
                    p.fname_id,
                    f.name AS prefix_name,
                    p.name AS first_name, 
                    p.sname AS last_name,
                    p.dep_id,
                    p.group_id,
                    p.phone,
                    p.bank_account,
                    p.bank_comment,
                    p.st
                  FROM " . $this->table_name . " u
                  LEFT JOIN profile p ON u.id = p.user_id
                  LEFT JOIN fname f ON p.fname_id = f.id
                  ORDER BY u.id DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // ฟังก์ชันสร้าง UUID แบบ native
    private function generateUuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ฟังก์ชันเพิ่มผู้ใช้งานใหม่ (อัปเดตให้รองรับฟิลด์ใหม่)
    public function createUser($data) {
        $checkQuery = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':username', $data['username']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ["success" => false, "message" => "ชื่อผู้ใช้งานนี้ (Username) มีอยู่ในระบบแล้ว"];
        }

        try {
            $this->conn->beginTransaction();

            // 1. สร้าง UUID ใหม่
            $newUserId = $this->generateUuid();

            // 2. บันทึกลงตาราง user
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $queryUser = "INSERT INTO " . $this->table_name . " (id, username, password_hash, role, status, created_at) 
                          VALUES (:id, :username, :password_hash, :role, 10, NOW())";
            $stmt = $this->conn->prepare($queryUser);
            $stmt->bindParam(':id', $newUserId); // ใช้วิธีนี้แทน
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password_hash', $hashedPassword);
            $stmt->bindParam(':role', $data['role']);
            $stmt->execute();
            // 3. บันทึกลงตาราง profile
            $queryProfile = "INSERT INTO profile (user_id, fname_id, name, sname, dep_id, group_id, phone, bank_account, bank_comment, st) 
                             VALUES (:user_id, :fname_id, :name, :sname, :dep_id, :group_id, :phone, :bank_account, :bank_comment, :st)";   
            $stmtProfile = $this->conn->prepare($queryProfile);
            $stmtProfile->bindParam(':user_id', $newUserId);
            $stmtProfile->bindParam(':fname_id', $data['fname_id']);
            $stmtProfile->bindParam(':name', $data['first_name']);
            $stmtProfile->bindParam(':sname', $data['last_name']);
            $stmtProfile->bindParam(':dep_id', $data['dep_id']);
            $stmtProfile->bindParam(':group_id', $data['group_id']);
            $stmtProfile->bindParam(':phone', $data['phone']);
            $stmtProfile->bindParam(':bank_account', $data['bank_account']);
            $stmtProfile->bindParam(':bank_comment', $data['bank_comment']);
            $stmtProfile->bindParam(':st', $data['st']);
            $stmtProfile->execute();

            

            $this->conn->commit();
            return ["success" => true, "message" => "เพิ่มสมาชิกสำเร็จ"];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()];
        }
    }

    // ฟังก์ชันอัปเดตข้อมูลผู้ใช้ (อัปเดตให้รองรับฟิลด์ใหม่)
    public function updateUser($data) {
        try {
            $this->conn->beginTransaction();

            // 1. อัปเดตตาราง user
            $queryUser = "UPDATE " . $this->table_name . " SET role = :role";
            if (!empty($data['password'])) {
                $queryUser .= ", password_hash = :password_hash";
            }
            $queryUser .= " WHERE id = :id";

            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->bindParam(':role', $data['role']);
            $stmtUser->bindParam(':id', $data['id']);
            
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmtUser->bindParam(':password_hash', $hashedPassword);
            }
            $stmtUser->execute();

            // จัดการค่าว่างให้เป็น NULL
            $fname_id = !empty($data['fname_id']) ? $data['fname_id'] : null;
            $dep_id = !empty($data['dep_id']) ? $data['dep_id'] : null;
            $group_id = !empty($data['group_id']) ? $data['group_id'] : null;
            $st = !empty($data['st']) ? $data['st'] : 0;

            // 2. อัปเดตตาราง profile
            $queryProfile = "UPDATE profile SET 
                                fname_id = :fname_id, 
                                name = :name, 
                                sname = :sname, 
                                dep_id = :dep_id, 
                                group_id = :group_id, 
                                phone = :phone, 
                                bank_account = :bank_account, 
                                bank_comment = :bank_comment, 
                                st = :st 
                             WHERE user_id = :user_id";
            $stmtProfile = $this->conn->prepare($queryProfile);
            $stmtProfile->bindParam(':fname_id', $fname_id);
            $stmtProfile->bindParam(':name', $data['first_name']);
            $stmtProfile->bindParam(':sname', $data['last_name']);
            $stmtProfile->bindParam(':dep_id', $dep_id);
            $stmtProfile->bindParam(':group_id', $group_id);
            $stmtProfile->bindParam(':phone', $data['phone']);
            $stmtProfile->bindParam(':bank_account', $data['bank_account']);
            $stmtProfile->bindParam(':bank_comment', $data['bank_comment']);
            $stmtProfile->bindParam(':st', $st);
            $stmtProfile->bindParam(':user_id', $data['id']);
            $stmtProfile->execute();

            $this->conn->commit();
            return ["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()];
        }
    }
    
    // ฟังก์ชันเปลี่ยนสถานะผู้ใช้งาน (เปิด/ปิด)
    public function toggleStatus($userId, $newStatus) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // ฟังก์ชันดึงข้อมูลสำหรับทำ Dropdown (คำนำหน้า, ตำแหน่ง, กลุ่มงาน)
    public function getFormOptions() {
        $options = [
            "fnames" => [],
            "deps" => [],
            "groups" => []
        ];

        // ดึงคำนำหน้า
        $stmtFname = $this->conn->query("SELECT id, name FROM fname ORDER BY id ASC");
        $options['fnames'] = $stmtFname->fetchAll(PDO::FETCH_ASSOC);

        // ดึงตำแหน่ง
        $stmtDep = $this->conn->query("SELECT id, name FROM dep ORDER BY id ASC");
        $options['deps'] = $stmtDep->fetchAll(PDO::FETCH_ASSOC);

        // ดึงกลุ่มงาน
        $stmtGroup = $this->conn->query("SELECT id, name FROM `group` ORDER BY id ASC");
        $options['groups'] = $stmtGroup->fetchAll(PDO::FETCH_ASSOC);

        return $options;
    }
}
?>