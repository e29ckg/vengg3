<?php
// backend/src/Models/Setting.php

class Setting {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ในไฟล์ Setting.php เพิ่มชื่อตารางที่อนุญาต
    public function getAll($table) {
        $allowed = ['dep', 'group', 'fname', 'ven_name', 'ven_name_sub', 'ven_com', 'sign_name', 'agency_config'];
        if (!in_array($table, $allowed)) return [];

        $query = "SELECT * FROM `$table`";
        
        // 🌟 ดักจับ Soft Delete ให้แสดงเฉพาะข้อมูลที่ยังไม่ถูกลบ (status = 1)
        if ($table === 'ven_name' || $table === 'ven_name_sub') {
            $query .= " WHERE status = 1";
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันพิเศษสำหรับดึงข้อมูลหน่วยงาน (ดึงแค่แถวแรก)
    // ============================================================
    // 🏢 การตั้งค่าหน่วยงานและผู้ลงนาม (Agency Settings)
    // ============================================================

    // 1. ดึงข้อมูลตั้งค่าหน่วยงาน
    public function getAgencySettings() {
        try {
            $query = "SELECT * FROM agency_settings WHERE id = 1 LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // แปลงข้อมูล JSON String จากฐานข้อมูล กลับเป็น Array ให้ Vue.js นำไปลูปได้
                $row['directors'] = !empty($row['directors']) ? json_decode($row['directors'], true) : [];
                $row['admins']    = !empty($row['admins']) ? json_decode($row['admins'], true) : [];
                $row['finances']  = !empty($row['finances']) ? json_decode($row['finances'], true) : [];
            } else {
                // ถ้าฐานข้อมูลยังไม่มีแถว id=1 ให้คืนค่าว่างเปล่ากลับไป
                $row = [
                    'agency_name' => '',
                    'directors' => [],
                    'admins' => [],
                    'finances' => []
                ];
            }

            return $row;
        } catch (PDOException $e) {
            error_log("Get Agency Settings Error: " . $e->getMessage());
            return false;
        }
    }

    // 2. อัปเดตข้อมูลหน่วยงาน (แพ็ค Array เป็น JSON ลง Database)
    public function updateAgencySettings($data) {
        try {
            // เช็คก่อนว่ามีแถว id=1 หรือยัง
            $checkStmt = $this->conn->query("SELECT id FROM agency_settings WHERE id = 1");
            $exists = $checkStmt->fetch();

            if ($exists) {
                // อัปเดตข้อมูลเดิม
                $sql = "UPDATE agency_settings SET 
                            agency_name = :agency_name, 
                            directors = :directors, 
                            admins = :admins, 
                            finances = :finances 
                        WHERE id = 1";
            } else {
                // สร้างใหม่ (กรณีเพิ่งติดตั้งระบบครั้งแรก)
                $sql = "INSERT INTO agency_settings (id, agency_name, directors, admins, finances) 
                        VALUES (1, :agency_name, :directors, :admins, :finances)";
            }

            $stmt = $this->conn->prepare($sql);
            
            // ใช้ JSON_UNESCAPED_UNICODE เพื่อให้ภาษาไทยในฐานข้อมูลยังอ่านออก ไม่เป็นรหัสต่างดาว
            return $stmt->execute([
                ':agency_name' => $data['agency_name'] ?? '',
                ':directors'   => json_encode($data['directors'] ?? [], JSON_UNESCAPED_UNICODE),
                ':admins'      => json_encode($data['admins'] ?? [], JSON_UNESCAPED_UNICODE),
                ':finances'    => json_encode($data['finances'] ?? [], JSON_UNESCAPED_UNICODE)
            ]);

        } catch (PDOException $e) {
            error_log("Update Agency Settings Error: " . $e->getMessage());
            return false;
        }
    }

    // เพิ่มข้อมูลใหม่
    public function create($table, $name) {
        $query = "INSERT INTO `$table` (name) VALUES (:name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    // แก้ไขข้อมูล
    public function update($table, $id, $name) {
        $query = "UPDATE `$table` SET name = :name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ลบข้อมูล (ปรับปรุงให้รองรับ Soft Delete ถ้าเป็นตารางเวร)
    public function delete($table, $id) {
        if ($table === 'ven_name' || $table === 'ven_name_sub') {
            $query = "UPDATE `$table` SET status = 0 WHERE id = :id";
        } else {
            $query = "DELETE FROM `$table` WHERE id = :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ดึงข้อมูลชื่อเวรหลัก พร้อมหน้าที่ย่อยที่สังกัดอยู่ (ประกอบร่างกันเลย)
    public function getVenFullData() {
        // 🌟 ดึงชื่อเวรหลักทั้งหมด (เอาเฉพาะที่ยังไม่ลบ status = 1)
        $queryName = "SELECT * FROM ven_name WHERE status = 1 ORDER BY srt ASC";
        $stmtName = $this->conn->query($queryName);
        $venNames = $stmtName->fetchAll(PDO::FETCH_ASSOC);

        // 🌟 ดึงหน้าที่ย่อยทั้งหมด (เอาเฉพาะที่ยังไม่ลบ status = 1)
        $querySub = "SELECT * FROM ven_name_sub WHERE status = 1 ORDER BY srt ASC, id ASC";
        $stmtSub = $this->conn->query($querySub);
        $venSubs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        // นำหน้าที่ย่อย ไปจัดกลุ่มใส่ในเวรหลักแต่ละอัน
        foreach ($venNames as &$ven) {
            $ven['subs'] = array_values(array_filter($venSubs, function($sub) use ($ven) {
                return $sub['ven_name_id'] == $ven['id'];
            }));
        }

        return $venNames;
    }

    public function getVenNames() {
        $query = "SELECT * FROM ven_name WHERE status = 1 ORDER BY srt ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
   
    // ==========================================
    // 4. ลบ ข้อมูลผ่าน DeleteTable (อัปเดตเป็น Soft Delete สำหรับเวร)
    // ==========================================

    public function deleteTable($table, $id) {
        try {
            $this->conn->beginTransaction();

            if ($table === 'ven_name') {
                // 🌟 เปลี่ยนเป็น Soft Delete
                $querySub = "UPDATE ven_name_sub SET status = 0 WHERE ven_name_id = :id";
                $stmtSub = $this->conn->prepare($querySub);
                $stmtSub->execute([':id' => $id]);
                
                $query = "UPDATE `$table` SET status = 0 WHERE id = :id";
            } 
            elseif ($table === 'ven_name_sub') {
                // 🌟 เปลี่ยนเป็น Soft Delete
                $query = "UPDATE `$table` SET status = 0 WHERE id = :id";
            } 
            else {
                // ลบข้อมูลจริงสำหรับตารางอื่นๆ
                $query = "DELETE FROM `$table` WHERE id = :id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getById($table, $id) {
        $allowed = ['ven_name', 'ven_name_sub']; // ตารางที่อนุญาต
        if (!in_array($table, $allowed)) return null;

        $query = "SELECT * FROM `$table` WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ดึงรายชื่อหน้าที่ย่อยทั้งหมด พร้อมรายชื่อคนที่ได้รับมอบหมาย
    public function getVenUserList() {
        // 🌟 ดึงหน้าที่ย่อยทั้งหมด (เฉพาะที่ยังไม่โดนลบ)
        $querySub = "SELECT s.id, s.name as sub_name, n.name as main_name 
                     FROM ven_name_sub s 
                     JOIN ven_name n ON s.ven_name_id = n.id 
                     WHERE s.status = 1 AND n.status = 1
                     ORDER BY n.srt ASC, s.srt ASC";
        $stmtSub = $this->conn->query($querySub);
        $subs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        // ดึงข้อมูลการจับคู่ (ven_user) พร้อมชื่อจริงจากตาราง profile
        foreach ($subs as &$sub) {
            $queryUser = "SELECT vu.id as vu_id, u.id as user_id, p.fname_id, f.name as prefix, p.name, p.sname 
                          FROM ven_user vu
                          JOIN user u ON vu.user_id = u.id
                          JOIN profile p ON u.id = p.user_id
                          LEFT JOIN fname f ON p.fname_id = f.id
                          WHERE vu.ven_name_sub_id = :sub_id
                          ORDER BY vu.order ASC";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->bindParam(':sub_id', $sub['id']);
            $stmtUser->execute();
            $sub['users'] = $stmtUser->fetchAll(PDO::FETCH_ASSOC);
        }
        return $subs;
    }

    public function getUsersBySubId($sub_id) {
        $query = "SELECT vu.id as vu_id, vu.order_num, u.id as user_id, p.prefix_name, p.first_name, p.last_name, CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, ''), ' ', IFNULL(p.last_name, '')) AS full_name
                  FROM ven_user vu
                  JOIN user u ON vu.user_id = u.id
                  JOIN profile p ON u.id = p.user_id
                  WHERE vu.ven_name_sub_id = :sub_id
                    AND u.status = 10     /* เช็คว่ารหัสยังไม่ถูกล็อค */
                    AND u.is_deleted = 0  /* เช็ค Soft Delete (ยังไม่โดนลบ) */
                  ORDER BY vu.srt ASC, vu.order_num ASC, vu.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sub_id', $sub_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==========================================
    // ส่วนจัดการคำสั่งเวร (Ven Command)
    // ==========================================
    
    // ดึงรายการคำสั่งทั้งหมด (Join ชื่อเวรมาด้วย)
    public function getVenCommands() {
        $query = "SELECT c.*, n.name as ven_name_title 
                  FROM ven_com c 
                  LEFT JOIN ven_name n ON c.ven_name_id = n.id 
                  ORDER BY c.ven_month DESC, c.com_date DESC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // เพิ่มคำสั่งใหม่
    public function createVenCommand($data) {
        $query = "INSERT INTO ven_com (com_num, com_date, ven_month, ven_name_id, status, ven_com_days) 
                  VALUES (:com_num, :com_date, :ven_month, :ven_name_id, 0, :ven_com_days)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':com_num' => $data['com_num'],
            ':com_date' => $data['com_date'],
            ':ven_month' => $data['ven_month'],
            ':ven_name_id' => $data['ven_name_id'],
            ':ven_com_days' => $data['ven_com_days'] ?? '' 
        ]);
    }

    // แก้ไขคำสั่ง
    public function updateVenCommand($data) {
        $query = "UPDATE ven_com SET com_num = :com_num, com_date = :com_date, 
                                     ven_month = :ven_month, ven_name_id = :ven_name_id,
                                     ven_com_days = :ven_com_days 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':com_num' => $data['com_num'],
            ':com_date' => $data['com_date'],
            ':ven_month' => $data['ven_month'],
            ':ven_name_id' => $data['ven_name_id'],
            ':ven_com_days' => $data['ven_com_days'] ?? '',
            ':id' => $data['id']
        ]);
    }

    // เปิด-ปิดสถานะคำสั่ง (Toggle Status)
    public function toggleVenCommandStatus($id, $status) {
        $query = "UPDATE ven_com SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // ลบคำสั่ง
    public function deleteVenCommand($id) {
        try {
            $this->conn->beginTransaction();
            $stmtSchedule = $this->conn->prepare("DELETE FROM ven_schedule WHERE ven_com_id = :id");
            $stmtSchedule->execute([':id' => $id]);
            $stmtCom = $this->conn->prepare("DELETE FROM ven_com WHERE id = :id");
            $stmtCom->execute([':id' => $id]);
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error deleting VenCommand: " . $e->getMessage());
            return false;
        }
    }

    // อัปเดตสถานะคำสั่งเวร
    public function updateCommandStatus($id, $status) {
        $query = "UPDATE ven_com SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }

    // ==========================================
    // ส่วนจัดการตารางเวร (Ven Schedule)
    // ==========================================

    // 1. ดึงข้อมูลเวรทั้งหมดในเดือนที่เลือก เพื่อเอาไปลงปฏิทิน
    public function getSchedulesByMonth($year_month) {
        $query = "SELECT s.id, s.ven_date as date, DAY(s.ven_date) as day, 
                         s.user_id, CONCAT_WS(' ', CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, '')), p.last_name) AS user_name,
                         s.ven_com_id as com_id, c.com_num,
                         s.ven_name_sub_id as sub_id, sub.name as sub_name, sub.color,
                         n.dn as shift_type
                  FROM ven_schedule s
                  JOIN user u ON s.user_id = u.id
                  JOIN profile p ON u.id = p.user_id
                  LEFT JOIN fname f ON p.fname_id = f.id
                  JOIN ven_com c ON s.ven_com_id = c.id
                  JOIN ven_name_sub sub ON s.ven_name_sub_id = sub.id
                  JOIN ven_name n ON c.ven_name_id = n.id
                  WHERE DATE_FORMAT(s.ven_date, '%Y-%m') = :year_month
                  ORDER BY s.ven_date ASC, n.srt ASC, sub.srt ASC, s.id ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year_month', $year_month);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. บันทึกคนลงเวร (Drag & Drop)
    public function addSchedule($data) {
        $query = "INSERT INTO ven_schedule (ven_date, ven_com_id, ven_name_sub_id, user_id, status) 
                  VALUES (:ven_date, :com_id, :sub_id, :user_id, :status)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':ven_date' => $data['date'],
            ':com_id'   => $data['com_id'],
            ':sub_id'   => $data['sub_id'],
            ':user_id'  => $data['user_id'],
            ':status'   => 1
        ]);
    }

    // 3. ลบคนออกจากเวร (กดปุ่ม x)
    public function removeSchedule($id) {
        $query = "DELETE FROM ven_schedule WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    // 1. ดึงตารางเวรของตัวเอง
    public function getMySchedules($user_id) {
        $query = "SELECT s.*, c.com_num, sub.name as sub_name, sub.color
                  FROM ven_schedule s
                  JOIN ven_com c ON s.ven_com_id = c.id
                  JOIN ven_name_sub sub ON s.ven_name_sub_id = sub.id
                  WHERE s.user_id = :user_id AND c.status = 2 AND s.ven_date >= CURDATE()
                  ORDER BY s.ven_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. สร้างคำขอแลกเวร
    public function createSwapRequest($data) {
        $query = "INSERT INTO ven_change (s1_id, user1_id, s2_id, user2_id, status) 
                  VALUES (:s1, :u1, :s2, :u2, 0)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':s1' => $data['s1_id'], ':u1' => $data['user1_id'],
            ':s2' => $data['s2_id'], ':u2' => $data['user2_id']
        ]);
    }

    // 3. จัดการสลับเวรเมื่อเพื่อนกดยอมรับ
    public function approveSwap($change_id) {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("SELECT * FROM ven_change WHERE id = ?");
            $stmt->execute([$change_id]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            $upd1 = $this->conn->prepare("UPDATE ven_schedule SET user_id = ? WHERE id = ?");
            $upd1->execute([$req['user2_id'], $req['s1_id']]); 
            
            $upd2 = $this->conn->prepare("UPDATE ven_schedule SET user_id = ? WHERE id = ?");
            $upd2->execute([$req['user1_id'], $req['s2_id']]); 

            $this->conn->prepare("UPDATE ven_change SET status = 1 WHERE id = ?")->execute([$change_id]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // โอนเวรให้ผู้อื่นโดยตรง พร้อมรันเลขที่เอกสาร
    public function transferShift($schedule_id, $new_user_id) {
        try {
            // โค้ดส่วนนี้ยังไม่ได้เริ่ม Transaction (ถ้า Error ตรงนี้ จะโดดไป Catch)
            $stmtCheck = $this->conn->prepare("SELECT user_id, ven_date FROM ven_schedule WHERE id = :id");
            $stmtCheck->execute([':id' => $schedule_id]);
            $shift = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$shift) return false;

            // 🌟 แก้ไขการเช็คสวิตช์อนุญาตเปลี่ยนเวรย้อนหลัง
            $stmtSetting = $this->conn->prepare("SELECT allow_retroactive_swap FROM system_settings WHERE id = 1");
            $stmtSetting->execute();
            $settingRow = $stmtSetting->fetch(PDO::FETCH_ASSOC);
            $allow_retro_transfer = ($settingRow && $settingRow['allow_retroactive_swap'] == 1); 

            if (!$allow_retro_transfer) {
                if (strtotime($shift['ven_date']) < strtotime(date('Y-m-d'))) {
                    return false; // ถ้าไม่อนุญาต และเป็นเวรที่ผ่านมาแล้ว ให้ปฏิเสธการโอน
                }
            }

            if (!$allow_retro_transfer) {
                if (strtotime($shift['ven_date']) < strtotime(date('Y-m-d'))) {
                    return false; 
                }
            }

            // 🌟 เริ่ม Transaction ตรงนี้
            $this->conn->beginTransaction();

            $old_user_id = $shift['user_id'];

            $queryUpdate = "UPDATE ven_schedule SET user_id = :new_user_id WHERE id = :schedule_id";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->execute([
                ':new_user_id' => $new_user_id,
                ':schedule_id' => $schedule_id
            ]);

            $yearMonth = date("ym"); 
            $prefix = "VC-" . $yearMonth . "-";

            $sqlLast = "SELECT change_no FROM ven_change WHERE change_no LIKE :prefix ORDER BY id DESC LIMIT 1";
            $stmtLast = $this->conn->prepare($sqlLast);
            $stmtLast->execute([':prefix' => $prefix . '%']);
            $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);

            $nextNumber = 1;
            if ($lastRow && !empty($lastRow['change_no'])) {
                $parts = explode("-", $lastRow['change_no']);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            }
            $new_change_no = $prefix . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

            if ($old_user_id) {
                $queryLog = "INSERT INTO ven_change (change_no, s1_id, user1_id, user2_id, status, created_at) 
                             VALUES (:change_no, :s1_id, :user1_id, :user2_id, 0, NOW())";
                $stmtLog = $this->conn->prepare($queryLog);
                $stmtLog->execute([
                    ':change_no' => $new_change_no,
                    ':s1_id' => $schedule_id,
                    ':user1_id' => $old_user_id,
                    ':user2_id' => $new_user_id
                ]);
            }

            $this->conn->commit();
            return true;

        } catch(PDOException $e) {
            // 🌟 แก้ไขบล็อก Catch ให้เช็คก่อนว่าอยู่ใน Transaction หรือไม่
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Transfer Shift Error: " . $e->getMessage());
            return false;
        }
    }

    // ดึงข้อมูลช่วงเวลาเวรทั้งหมด
    public function getVenTimes() {
        $query = "SELECT * FROM ven_time ORDER BY srt ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ดึงรายการขอเปลี่ยนเวรทั้งหมด
    public function getAllChangeRequests() {
        $query = "SELECT vc.id, vc.change_no, vc.status, vc.created_at, 
                         vs.ven_date, vn.name AS duty_main, vns.name AS duty_role,
                         CONCAT_WS(' ', CONCAT(IFNULL(p1.prefix_name, ''), IFNULL(p1.first_name, '')), p1.last_name) AS user1_name,
                         CONCAT_WS(' ', CONCAT(IFNULL(p2.prefix_name, ''), IFNULL(p2.first_name, '')), p2.last_name) AS user2_name
                  FROM ven_change vc
                  JOIN ven_schedule vs ON vc.s1_id = vs.id
                  LEFT JOIN ven_name_sub vns ON vs.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vcom ON vs.ven_com_id = vcom.id
                  LEFT JOIN ven_name vn ON vcom.ven_name_id = vn.id
                  LEFT JOIN profile p1 ON vc.user1_id = p1.user_id
                  LEFT JOIN profile p2 ON vc.user2_id = p2.user_id
                  ORDER BY vc.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // อัปเดตสถานะการอนุมัติ (รองรับการแก้ไขสถานะย้อนหลัง)
    public function updateChangeStatus($id, $status) {
        try {
            $this->conn->beginTransaction();

            $stmtChange = $this->conn->prepare("SELECT s1_id, user1_id, user2_id FROM ven_change WHERE id = :id");
            $stmtChange->execute([':id' => $id]);
            $changeData = $stmtChange->fetch(PDO::FETCH_ASSOC);

            if (!$changeData) throw new Exception("ไม่พบข้อมูลใบเปลี่ยนเวร");

            $query = "UPDATE ven_change SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':status' => $status, ':id' => $id]);

            $targetUserId = ($status == 2) ? $changeData['user1_id'] : $changeData['user2_id'];

            $queryRev = "UPDATE ven_schedule SET user_id = :user_id WHERE id = :s1_id";
            $stmtRev = $this->conn->prepare($queryRev);
            $stmtRev->execute([
                ':user_id' => $targetUserId,
                ':s1_id' => $changeData['s1_id']
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // --- ตั้งค่า Telegram ---
    public function getTelegramSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM telegram_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateTelegramSettings($data) {
        try {
            $this->conn->beginTransaction();
            
            $sql = "UPDATE telegram_settings SET 
                        bot_token = :bot_token, 
                        chat_id = :chat_id, 
                        notify_confirmed = :notify_confirmed, 
                        notify_change_request = :notify_change_request, 
                        notify_approval = :notify_approval 
                    WHERE id = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':bot_token' => $data['bot_token'],
                ':chat_id' => $data['chat_id'],
                ':notify_confirmed' => $data['notify_confirmed'] ? 1 : 0,
                ':notify_change_request' => $data['notify_change_request'] ? 1 : 0,
                ':notify_approval' => $data['notify_approval'] ? 1 : 0
            ]);

            $this->conn->prepare("DELETE FROM telegram_notify_times")->execute();
            if (!empty($data['notify_times'])) {
                $stmtTime = $this->conn->prepare("INSERT INTO telegram_notify_times (send_time, notify_day, status) VALUES (:t, :d, :s)");
                foreach ($data['notify_times'] as $timeSlot) {
                    $stmtTime->execute([
                        ':t' => $timeSlot['send_time'],
                        ':d' => $timeSlot['notify_day'], 
                        ':s' => $timeSlot['status'] ? 1 : 0
                    ]);
                }
            }
            
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // ดึงการตั้งค่าระบบ
    public function getSystemSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // อัปเดตการตั้งค่าระบบ
    public function updateSystemSettings($data) {
        try {
            $sql = "UPDATE system_settings SET 
                        system_name = :system_name, 
                        allow_swap = :allow_swap, 
                        advance_swap_days = :advance_swap_days,
                        maintenance_mode = :maintenance_mode,
                        allow_retroactive_swap = :allow_retroactive_swap,
                        check_24h_consecutive = :check_24h_consecutive
                    WHERE id = 1";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':system_name' => $data['system_name'],
                ':allow_swap' => $data['allow_swap'],
                ':advance_swap_days' => $data['advance_swap_days'],
                ':maintenance_mode' => $data['maintenance_mode'],
                ':allow_retroactive_swap' => $data['allow_retroactive_swap'], 
                ':check_24h_consecutive' => $data['check_24h_consecutive']    
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ==========================================
    // ส่วนที่ 1: การตั้งค่าเวรหลัก และ หน้าที่ย่อย (เจาะจงเฉพาะ Component)
    // ==========================================

    public function getVenNameById($id) {
        $query = "SELECT * FROM ven_name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveVenName($data) {
        if (!empty($data['id'])) {
            $query = "UPDATE ven_name SET name = :name, dn = :dn, srt = :srt, name_full = :name_full WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':name' => $data['name'], ':dn' => $data['dn'], 
                ':srt' => $data['srt'], ':name_full' => $data['name_full'], ':id' => $data['id']
            ]);
        } else {
            // 🌟 เพิ่มสถานะ = 1 (ตอนเพิ่มใหม่)
            $query = "INSERT INTO ven_name (name, dn, srt, name_full, status) VALUES (:name, :dn, :srt, :name_full, 1)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':name' => $data['name'], ':dn' => $data['dn'], 
                ':srt' => $data['srt'], ':name_full' => $data['name_full']
            ]);
        }
    }

    public function deleteVenName($id) {
        try {
            $this->conn->beginTransaction();
            // 🌟 ซ่อนข้อมูลแทนการลบ
            $stmtSub = $this->conn->prepare("UPDATE ven_name_sub SET status = 0 WHERE ven_name_id = :id");
            $stmtSub->execute([':id' => $id]);
            $stmt = $this->conn->prepare("UPDATE ven_name SET status = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function saveSubDuty($data) {
        if (!empty($data['id'])) {
            $query = "UPDATE ven_name_sub SET name = :name, price = :price, color = :color, srt = :srt WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':name' => $data['name'], ':price' => $data['price'],
                ':color' => $data['color'], ':srt' => $data['srt'], ':id' => $data['id']
            ]);
        } else {
            // 🌟 เพิ่มสถานะ = 1 (ตอนเพิ่มใหม่)
            $query = "INSERT INTO ven_name_sub (ven_name_id, name, price, color, srt, status) VALUES (:ven_name_id, :name, :price, :color, :srt, 1)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':ven_name_id' => $data['ven_name_id'], ':name' => $data['name'],
                ':price' => $data['price'], ':color' => $data['color'], ':srt' => $data['srt']
            ]);
        }
    }

    public function deleteSubDuty($id) {
        // 🌟 ซ่อนข้อมูลแทนการลบ
        $stmt = $this->conn->prepare("UPDATE ven_name_sub SET status = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateSubDutyOrder($dataArray) {
        try {
            $this->conn->beginTransaction();
            $query = "UPDATE ven_name_sub SET srt = :srt WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            foreach ($dataArray as $item) {
                $stmt->execute([':srt' => $item['srt'], ':id' => $item['id']]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // ==========================================
    // ส่วนที่ 2: การจัดการคนเข้าเวร (ven_user)
    // ==========================================

    public function getAssignedUsersBySub($sub_id) {
        $query = "SELECT vu.id as vu_id, vu.user_id, vu.srt, f.name as prefix_name, p.first_name, p.last_name 
                  FROM ven_user vu
                  JOIN user u ON vu.user_id = u.id
                  JOIN profile p ON u.id = p.user_id
                  LEFT JOIN fname f ON p.fname_id = f.id
                  WHERE vu.ven_name_sub_id = :sub_id 
                  ORDER BY vu.srt ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':sub_id' => $sub_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addVenUser($sub_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT MAX(srt) as max_srt FROM ven_user WHERE ven_name_sub_id = :sub_id");
        $stmt->execute([':sub_id' => $sub_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_srt = ($row['max_srt'] !== null) ? $row['max_srt'] + 1 : 1;

        $query = "INSERT INTO ven_user (ven_name_sub_id, user_id, srt) VALUES (:sub_id, :user_id, :srt)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':sub_id' => $sub_id, ':user_id' => $user_id, ':srt' => $next_srt]);
    }

    public function removeVenUser($vu_id) {
        $query = "DELETE FROM ven_user WHERE id = :vu_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':vu_id' => $vu_id]);
    }

    public function updateVenUserOrder($ordered_ids) {
        try {
            $this->conn->beginTransaction();
            $query = "UPDATE ven_user SET srt = :srt WHERE id = :vu_id";
            $stmt = $this->conn->prepare($query);
            foreach ($ordered_ids as $index => $vu_id) {
                $stmt->execute([':srt' => $index + 1, ':vu_id' => $vu_id]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // ดึงประวัติการโอนเวร (เอาเฉพาะที่ตัวเองเป็นคนให้ หรือ ตัวเองเป็นคนรับ)
    public function getUserChangeHistory($user_id) {
        $query = "SELECT vc.id, vc.change_no, vc.status, vc.created_at, vc.s1_id,
                         vs.ven_date, vn.name AS duty_main, vns.name AS duty_role,
                         vc.user1_id, vc.user2_id,
                         vcom.com_num AS com_num, 
                         vcom.com_date AS com_date,
                         CONCAT_WS(' ', p1.prefix_name, p1.first_name, p1.last_name) AS user1_name,
                         CONCAT_WS(' ', p2.prefix_name, p2.first_name, p2.last_name) AS user2_name
                  FROM ven_change vc
                  JOIN ven_schedule vs ON vc.s1_id = vs.id
                  LEFT JOIN ven_name_sub vns ON vs.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vcom ON vs.ven_com_id = vcom.id
                  LEFT JOIN ven_name vn ON vcom.ven_name_id = vn.id
                  LEFT JOIN profile p1 ON vc.user1_id = p1.user_id
                  LEFT JOIN profile p2 ON vc.user2_id = p2.user_id
                  WHERE vc.user1_id = :u1 OR vc.user2_id = :u2
                  ORDER BY vc.created_at DESC
                  LIMIT 100";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':u1' => $user_id, ':u2' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันยกเลิกการขอโอนเวร
    public function cancelChangeRequest($change_id, $schedule_id, $current_user_id) {
        try {
            $this->conn->beginTransaction();

            // 1. เช็คก่อนว่าสถานะเป็น 0 (รออนุมัติ) และคนที่ลบเป็นเจ้าของเวรจริงๆ ใช่ไหม
            $stmtCheck = $this->conn->prepare("SELECT user1_id, status FROM ven_change WHERE id = :id");
            $stmtCheck->execute([':id' => $change_id]);
            $req = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$req || $req['status'] != 0 || $req['user1_id'] != $current_user_id) {
                throw new Exception("ไม่สามารถยกเลิกได้ (สถานะอาจเปลี่ยนไปแล้ว หรือคุณไม่ใช่ผู้ขอ)");
            }

            // 2. ดึงชื่อกลับมาเป็นของตัวเองในตาราง ven_schedule
            $stmtRev = $this->conn->prepare("UPDATE ven_schedule SET user_id = :u_id WHERE id = :s_id");
            $stmtRev->execute([':u_id' => $current_user_id, ':s_id' => $schedule_id]);

            // 3. ลบคำขอออกจากตาราง ven_change
            $stmtDel = $this->conn->prepare("DELETE FROM ven_change WHERE id = :id");
            $stmtDel->execute([':id' => $change_id]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Cancel Request Error: " . $e->getMessage());
            return false;
        }
    }

    
    
}