<?php
// backend/src/Models/User.php
require_once __DIR__ . '/../Services/SessionSecurity.php';

class User {
    private $conn;
    private $table_name = "user";
    private function preservesAdministrator($id, $role, $status, bool $deleted = false): bool {
        $lock = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $admins = $this->conn->query('SELECT id FROM user WHERE role = 9 AND status = 10 AND is_deleted = 0' . $lock)->fetchAll(PDO::FETCH_COLUMN);
        return !in_array((string)$id, array_map('strval', $admins), true) || count($admins) > 1 || (!$deleted && ($role === null || (int)$role === 9) && (int)$status === 10);
    }

    public function __construct($db) {
        $this->conn = $db;
    }

    // ฟังก์ชันตรวจสอบการล็อกอิน
    public function login($username, $password) {
        if (is_string($username)) $username = trim($username);
        if (!is_string($username) || !is_string($password) || strlen($username) > 128 || strlen($password) > 72) return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
        // ดึงข้อมูลผู้ใช้จากชื่อผู้ใช้
        $query = "SELECT u.id, u.username, u.password_hash, u.role, u.status, 
                         p.prefix_name, p.first_name, p.last_name, p.avatar 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN profile p ON u.id = p.user_id 
                  WHERE u.username = :username AND u.is_deleted = 0 LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $verified = password_verify($password, $row['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
        if ($row) {
            
            // เช็คว่าสถานะผู้ใช้ถูกระงับหรือไม่ (สมมติ 10 = ปกติ)
            if ($row['status'] != 10) {
                return ["success" => false, "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"];
            }

            // ตรวจสอบรหัสผ่านที่ส่งมา เทียบกับ Hash ในฐานข้อมูล
            if ($verified) {
                // รหัสผ่านถูกต้อง สร้าง Token 32 ตัวอักษร
                $token = SessionSecurity::issue();
                
                // บันทึก Token ลงในช่อง auth_key
                $this->updateAuthKey($row['id'], $token);

                $fullName = trim(($row['prefix_name'] ?? '').($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

                // ส่งข้อมูลผู้ใช้และ Token กลับไป
                return [
                    "success" => true,
                    "user" => [
                        "id" => $row['id'],
                        "username" => $row['username'],
                        "role" => $row['role'],
                        "fullname" => $fullName,               
                        "avatar" => $row['avatar']
                    ],
                    "token" => $token
                ];
            } else {
                return ["success" => false, "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"];
            }
        }
        
        return ["success" => false, "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"];
    }

    // ฟังก์ชันอัปเดต Token ลงฐานข้อมูล
    public function loginThrottleKey(string $username): string {
        $query = $this->conn->prepare('SELECT id FROM user WHERE username = ? LIMIT 1');
        $query->execute([trim($username)]);
        $id = $query->fetchColumn();
        return $id !== false ? 'id:' . $id : 'name:' . strtolower(trim($username));
    }
    private function updateAuthKey($id, $token) {
        $token = SessionSecurity::fingerprint($token);
        $query = "UPDATE " . $this->table_name . " SET auth_key = :token WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    // ฟังก์ชันตรวจสอบ Token ว่าถูกต้องและมีอยู่จริงหรือไม่ (สำหรับ Middleware)
    public function validateToken($token) {
        if (!is_string($token) || !SessionSecurity::valid($token)) return false;
        $token = SessionSecurity::fingerprint($token);
        $query = "SELECT u.id, u.username, u.role, p.avatar FROM user u LEFT JOIN profile p ON u.id = p.user_id WHERE u.auth_key = :token AND u.status = 10 AND u.is_deleted = 0 LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ถ้า Token ถูกต้อง ส่งข้อมูลผู้ใช้ (id, username, role) กลับไปให้ระบบรับรู้
            return $row;
        }
        
        return false; // Token ปลอม หรือถูกระงับ
    }

    // ฟังก์ชันดึงรายชื่อผู้ใช้ทั้งหมดสำหรับ Admin
    public function revokeSession($id): void {
        $this->conn->prepare('UPDATE user SET auth_key = NULL WHERE id = ?')->execute([$id]);
    }
    public function changePassword($id, $oldPassword, $newPassword): bool {
        if (!is_string($oldPassword) || strlen($oldPassword) > 72 || !SessionSecurity::passwordAllowed($newPassword)) return false;
        $query = $this->conn->prepare('SELECT password_hash FROM user WHERE id = ? AND is_deleted = 0 AND status = 10');
        $query->execute([$id]);
        $hash = $query->fetchColumn();
        if (!$hash || !password_verify($oldPassword, $hash)) return false;
        $update = $this->conn->prepare('UPDATE user SET password_hash = ?, auth_key = NULL WHERE id = ? AND password_hash = ?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id, $hash]);
        return $update->rowCount() === 1;
    }
    // ฟังก์ชันดึงรายชื่อผู้ใช้ทั้งหมด (อัปเดตให้ดึงข้อมูลครบทุกฟิลด์)
    public function getAllUsers() {
        $query = "SELECT 
                    u.id, 
                    u.username, 
                    u.role, 
                    u.status,
                    p.avatar,
                    p.prefix_name AS prefix_name,
                    p.first_name AS first_name, 
                    p.last_name AS last_name,
                    p.position AS position,
                    p.srt AS srt,
                    p.department AS department,
                    p.phone,
                    p.bank_account,
                    p.bank_comment,
                    p.st
                  FROM user u
                  LEFT JOIN profile p ON u.id = p.user_id
                  WHERE u.is_deleted = 0
                  ORDER BY p.srt ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getAllUsersForVenUser() {
        $query = "SELECT 
                    u.id, 
                    u.username, 
                    u.role, 
                    u.status,
                    p.avatar,
                    p.prefix_name AS prefix_name,
                    p.first_name AS first_name, 
                    p.last_name AS last_name,
                    p.position AS position,
                    p.srt AS srt,
                    p.department AS department,
                    p.phone,
                    p.bank_account,
                    p.bank_comment,
                    p.st
                  FROM user u
                  LEFT JOIN profile p ON u.id = p.user_id
                  WHERE u.is_deleted = 0 AND u.status = 10
                  ORDER BY p.srt ASC";
                  
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
        if (!is_string($data['username'] ?? null) || strlen($data['username']) > 128 || !preg_match('/\A[^\s\x00-\x1F\x7F]{1,128}\z/u', $data['username'])) return ['success'=>false,'message'=>'ชื่อผู้ใช้ยาวเกินไป หรือมีช่องว่างหรืออักขระควบคุม'];
        if (!SessionSecurity::passwordAllowed($data['password'] ?? null) || !in_array((string)($data['role'] ?? ''), ['1','2','3','9'], true)) return ['success' => false, 'message' => 'รหัสผ่านต้องยาว 12-72 bytes และบทบาทต้องถูกต้อง'];
        $checkQuery = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':username', $data['username']);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() !== false) {
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
            $queryProfile = "INSERT INTO profile (user_id, prefix_name, first_name, last_name, srt, position, department, phone, bank_account, bank_comment, st) 
                             VALUES (:user_id, :prefix_name, :first_name, :last_name, :srt, :position, :department, :phone, :bank_account, :bank_comment, :st)";   
            $stmtProfile = $this->conn->prepare($queryProfile);
            $stmtProfile->bindParam(':user_id', $newUserId);
            $stmtProfile->bindParam(':prefix_name', $data['prefix_name']);
            $stmtProfile->bindParam(':first_name', $data['first_name']);
            $stmtProfile->bindParam(':last_name', $data['last_name']);
            $stmtProfile->bindParam(':srt', $data['srt']);
            $stmtProfile->bindParam(':position', $data['position']);
            $stmtProfile->bindParam(':department', $data['department']);
            $stmtProfile->bindParam(':phone', $data['phone']);
            $stmtProfile->bindParam(':bank_account', $data['bank_account']);
            $stmtProfile->bindParam(':bank_comment', $data['bank_comment']);
            $stmtProfile->bindParam(':st', $data['st']);
            $stmtProfile->execute();

            

            $this->conn->commit();
            return ["success" => true, "message" => "เพิ่มสมาชิกสำเร็จ"];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Create user failed');
            return ["success" => false, "message" => "ไม่สามารถเพิ่มผู้ใช้ได้"];
        }
    }

    // ฟังก์ชันอัปเดตข้อมูลผู้ใช้ (อัปเดตให้รองรับฟิลด์ใหม่)
    public function updateUser($data) {
    if ((!empty($data['password']) && !SessionSecurity::passwordAllowed($data['password'])) || !in_array((string)($data['role'] ?? ''), ['1','2','3','9'], true) || !in_array((string)($data['status'] ?? '10'), ['0','10'], true)) return ['success' => false, 'message' => 'รหัสผ่าน บทบาท หรือสถานะไม่ถูกต้อง'];
    try {
        $this->conn->beginTransaction();

        // --- 1. เตรียมค่าพื้นฐาน ---
        $userId = $data['id'];
        // ใช้ isset เพื่อรองรับค่า 0 (ระงับ/ย้าย)
        $status = isset($data['status']) ? (int)$data['status'] : 10;
        $role = isset($data['role']) ? (int)$data['role'] : 1;
        $srt = isset($data['srt']) ? (int)$data['srt'] : 999;
        if (!$this->preservesAdministrator($userId, $role, $status)) {
            $this->conn->rollBack();
            return ['success'=>false,'message'=>'ต้องคงแอดมินที่เปิดใช้งานอย่างน้อยหนึ่งบัญชี','code'=>409];
        }

        // 🌟 กฎเหล็ก: ถ้า status เป็น 0 (ระงับ/ย้าย) ให้ srt เป็น 999 เสมอ
        if ($status === 0) {
            $srt = 999;
        }

        // --- 2. อัปเดตตาราง user ---
        $queryUser = "UPDATE " . $this->table_name . " SET role = :role, status = :status, auth_key = NULL";
        if (!empty($data['password'])) {
            $queryUser .= ", password_hash = :password_hash";
        }
        $queryUser .= " WHERE id = :id";

        $stmtUser = $this->conn->prepare($queryUser);
        $stmtUser->bindParam(':role', $role);
        $stmtUser->bindParam(':status', $status);
        $stmtUser->bindParam(':id', $userId);
        
        if (!empty($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmtUser->bindParam(':password_hash', $hashedPassword);
        }
        $stmtUser->execute();

        // --- 3. เตรียมข้อมูลสำหรับตาราง profile ---
        $prefix_name = !empty($data['prefix_name']) ? $data['prefix_name'] : null;
        $first_name  = !empty($data['first_name']) ? $data['first_name'] : null;
        $last_name   = !empty($data['last_name']) ? $data['last_name'] : null;
        $position    = !empty($data['position']) ? $data['position'] : null;
        $department   = !empty($data['department']) ? $data['department'] : null;
        $phone       = !empty($data['phone']) ? $data['phone'] : null;
        $bank_acc    = !empty($data['bank_account']) ? $data['bank_account'] : null;
        $bank_com    = !empty($data['bank_comment']) ? $data['bank_comment'] : null;

        // --- 4. อัปเดตตาราง profile ---
        // หมายเหตุ: ใช้ status = :status เพื่อให้สถานะตรงกันทั้ง 2 ตาราง
        $queryProfile = "UPDATE profile SET 
                            prefix_name = :prefix_name, 
                            first_name = :first_name, 
                            last_name = :last_name, 
                            srt = :srt, 
                            position = :position,                                 
                            department = :department, 
                            phone = :phone, 
                            bank_account = :bank_account, 
                            bank_comment = :bank_comment, 
                            status = :status 
                         WHERE user_id = :user_id";
                             
        $stmtProfile = $this->conn->prepare($queryProfile);
        $stmtProfile->bindParam(':prefix_name', $prefix_name);
        $stmtProfile->bindParam(':first_name', $first_name);
        $stmtProfile->bindParam(':last_name', $last_name);
        $stmtProfile->bindParam(':srt', $srt);
        $stmtProfile->bindParam(':position', $position);
        $stmtProfile->bindParam(':department', $department);
        $stmtProfile->bindParam(':phone', $phone);
        $stmtProfile->bindParam(':bank_account', $bank_acc);
        $stmtProfile->bindParam(':bank_comment', $bank_com);
        $stmtProfile->bindParam(':status', $status);
        $stmtProfile->bindParam(':user_id', $userId);
        $stmtProfile->execute();

        $this->conn->commit();
        return ["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"];

    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log('Update user failed');
        return ["success" => false, "message" => "ไม่สามารถอัปเดตผู้ใช้ได้"];
    }
}
    
    // ฟังก์ชันเปลี่ยนสถานะผู้ใช้งาน (เปิด/ปิด)
    public function toggleStatus($userId, $newStatus) {        
        if (!in_array((string)$newStatus, ['0','10'], true)) return false;
        $this->conn->beginTransaction();
        if (!$this->preservesAdministrator($userId, null, $newStatus)) { $this->conn->rollBack(); return false; }
        $query = "UPDATE " . $this->table_name . " SET status = :status, auth_key = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $this->conn->commit();
            return true;
        }
        $this->conn->rollBack();
        return false;
    }

    
    public function deleteUser($id) {
        $this->conn->beginTransaction();
        if (!$this->preservesAdministrator($id, null, 0, true)) { $this->conn->rollBack(); return false; }
        // 🌟 เปลี่ยนเป็นการอัปเดตสถานะ is_deleted = 1 แทนการลบข้อมูลจริง
        $query = "UPDATE user SET is_deleted = 1, auth_key = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        $this->conn->commit();
        return $result;
    }

    // ปรับลำดับอาวุโสไปอยู่ท้ายสุด (ใช้เมื่อถูกระงับ/โอนย้าย)
    public function setLowestSeniority($user_id) {
        $query = "UPDATE profile SET srt = 999 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $user_id
        ]);
    }
public function update_order($data) {
        try {
            $this->conn->beginTransaction();
            
            // วนลูปอัปเดตลำดับ (srt) ตาม ID
            $stmt = $this->conn->prepare("UPDATE profile SET srt = :srt WHERE user_id = :id");
            foreach ($data as $item) {
                $stmt->execute([
                    ':srt' => $item['srt'],
                    ':id'  => $item['id']
                ]);
            }
            
            $this->conn->commit();
            
            // 🌟 เพิ่มบรรทัดนี้: เพื่อส่งสัญญาณกลับไปว่าทำสำเร็จแล้ว
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // 🌟 ดึงข้อมูลโปรไฟล์ส่วนตัว
    public function getProfile($userId) {
        $stmt = $this->conn->prepare("SELECT avatar, prefix_name, first_name, last_name, position, department, phone, bank_account, bank_comment FROM profile WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAvatar($userId) {
        $stmt = $this->conn->prepare("SELECT avatar FROM profile WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    // อัปเดตชื่อไฟล์รูปโปรไฟล์ใหม่
    public function updateAvatar($userId, $fileName) {
        $stmt = $this->conn->prepare("UPDATE profile SET avatar = ? WHERE user_id = ?");
        return $stmt->execute([$fileName, $userId]);
    }

    // 🌟 อัปเดตข้อมูลโปรไฟล์ส่วนตัว
    public function updateProfile($userId, $data) {
        $sql = "UPDATE profile 
                SET prefix_name=?, first_name=?, last_name=?, position=?, department=?, phone=?, bank_account=?, bank_comment=? 
                WHERE user_id=?";
        
        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([
            $data['prefix_name'] ?? null, 
            $data['first_name'] ?? null, 
            $data['last_name'] ?? null, 
            $data['position'] ?? null, 
            $data['department'] ?? null, 
            $data['phone'] ?? null, 
            $data['bank_account'] ?? null, 
            $data['bank_comment'] ?? null, 
            $userId
        ]);
    }
}
?>
