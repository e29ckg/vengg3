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

        $query = "SELECT * FROM `$table` ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันพิเศษสำหรับดึงข้อมูลหน่วยงาน (ดึงแค่แถวแรก)
    // 1. ดึงข้อมูลตั้งค่าหน่วยงาน
    public function getAgencyConfig() {
        $query = "SELECT * FROM agency_config WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. อัปเดตข้อมูลหน่วยงาน
    public function updateAgencyConfig($data) {
        $query = "UPDATE agency_config SET 
                    agency_name = :agency_name,
                    agency_short_name = :agency_short_name,
                    director_name = :director_name,
                    director_position = :director_position,
                    admin_name = :admin_name,
                    admin_position = :admin_position
                  WHERE id = 1";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':agency_name' => $data['agency_name'],
            ':agency_short_name' => $data['agency_short_name'],
            ':director_name' => $data['director_name'],
            ':director_position' => $data['director_position'],
            ':admin_name' => $data['admin_name'],
            ':admin_position' => $data['admin_position']
        ]);
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

    // ลบข้อมูล
    public function delete($table, $id) {
        $query = "DELETE FROM `$table` WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ดึงข้อมูลชื่อเวรหลัก พร้อมหน้าที่ย่อยที่สังกัดอยู่ (ประกอบร่างกันเลย)
    public function getVenFullData() {
        // ดึงชื่อเวรหลักทั้งหมด
        $queryName = "SELECT * FROM ven_name ORDER BY srt ASC";
        $stmtName = $this->conn->query($queryName);
        $venNames = $stmtName->fetchAll(PDO::FETCH_ASSOC);

        // ดึงหน้าที่ย่อยทั้งหมด
        $querySub = "SELECT * FROM ven_name_sub ORDER BY srt ASC, id ASC";
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

    // เพิ่มหน้าที่ย่อย (มีราคาและสี)
    public function createVenSub($data) {
        $query = "INSERT INTO `ven_name_sub` (ven_name_id, name, price, color) 
                  VALUES (:ven_name_id, :name, :price, :color)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ven_name_id', $data['ven_name_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':color', $data['color']);
        return $stmt->execute();
    }

    // อัปเดตหน้าที่ย่อย
    public function updateVenSub($data) {
        $query = "UPDATE `ven_name_sub` SET name = :name, price = :price, color = :color WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':color', $data['color']);
        $stmt->bindParam(':id', $data['id']);
        return $stmt->execute();
    }

    // อัปเดตฟังก์ชัน create และ update ให้รองรับโครงสร้าง ven_name แบบเต็ม
    public function createVenName($data) {
        $query = "INSERT INTO `ven_name` (srt, name, dn, name_full) VALUES (:srt, :name, :dn, :name_full)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':srt', $data['srt']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':dn', $data['dn']);
        $stmt->bindParam(':name_full', $data['name_full']);
        return $stmt->execute();
    }

    public function updateVenName($data) {
        $query = "UPDATE `ven_name` SET srt = :srt, name = :name, dn = :dn, name_full = :name_full WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':srt', $data['srt']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':dn', $data['dn']);
        $stmt->bindParam(':name_full', $data['name_full']);
        $stmt->bindParam(':id', $data['id']);
        return $stmt->execute();
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
        // 1. ดึงหน้าที่ย่อยทั้งหมด
        $querySub = "SELECT s.id, s.name as sub_name, n.name as main_name 
                     FROM ven_name_sub s 
                     JOIN ven_name n ON s.ven_name_id = n.id 
                     ORDER BY n.srt ASC, s.srt ASC";
        $stmtSub = $this->conn->query($querySub);
        $subs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        // 2. ดึงข้อมูลการจับคู่ (ven_user) พร้อมชื่อจริงจากตาราง profile
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

    // เพิ่มคนเข้าสู่รายชื่อหน้าที่นั้นๆ
    public function addVenUser($sub_id, $user_id) {
        // เช็คก่อนว่าซ้ำไหม
        $check = "SELECT id FROM ven_user WHERE ven_name_sub_id = :sub_id AND user_id = :user_id";
        $stmtCheck = $this->conn->prepare($check);
        $stmtCheck->execute(['sub_id' => $sub_id, 'user_id' => $user_id]);
        if($stmtCheck->rowCount() > 0) return true;

        $query = "INSERT INTO ven_user (ven_name_sub_id, user_id) VALUES (:sub_id, :user_id)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['sub_id' => $sub_id, 'user_id' => $user_id]);
    }

    // ลบคนออกจากรายชื่อหน้าที่นั้นๆ
    public function removeVenUser($vu_id) {
        $query = "DELETE FROM ven_user WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $vu_id]);
    }

    public function getUsersBySubId($sub_id) {
        $query = "SELECT vu.id as vu_id, vu.order_num, u.id as user_id, p.prefix_name, p.first_name, p.last_name, CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, ''), ' ', IFNULL(p.last_name, '')) AS full_name
                  FROM ven_user vu
                  JOIN user u ON vu.user_id = u.id
                  JOIN profile p ON u.id = p.user_id
                  WHERE vu.ven_name_sub_id = :sub_id
                    AND u.status = 10     /* เช็คว่ารหัสยังไม่ถูกล็อค */
                    AND u.is_deleted = 0  /* เช็ค Soft Delete (ยังไม่โดนลบ) */
                  ORDER BY vu.order_num ASC, vu.id ASC"; // 🌟 สั่งให้เรียงตาม order_num
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sub_id', $sub_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    // อัปเดตลำดับเรียงคนในหน้าที่ย่อย
    public function updateVenUserOrder($ordered_ids) {
        try {
            $this->conn->beginTransaction();
            foreach ($ordered_ids as $index => $vu_id) {
                $query = "UPDATE ven_user SET order_num = :order_num WHERE id = :vu_id";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(['order_num' => $index + 1, 'vu_id' => $vu_id]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
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
            ':ven_com_days' => $data['ven_com_days'] ?? '' // 🌟 เพิ่มบรรทัดนี้
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
            ':ven_com_days' => $data['ven_com_days'] ?? '', // 🌟 เพิ่มบรรทัดนี้
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
            // 🌟 1. เริ่ม Transaction (ถ้าลบพังกลางทาง ข้อมูลจะถูกย้อนกลับทั้งหมด)
            $this->conn->beginTransaction();

            // 🌟 2. ลบรายชื่อเวรที่จัดไว้ก่อน (ตารางลูก)
            $stmtSchedule = $this->conn->prepare("DELETE FROM ven_schedule WHERE ven_com_id = :id");
            $stmtSchedule->execute([':id' => $id]);

            // 🌟 3. ค่อยลบคำสั่ง (ตารางแม่)
            $stmtCom = $this->conn->prepare("DELETE FROM ven_com WHERE id = :id");
            $stmtCom->execute([':id' => $id]);

            // 🌟 4. ยืนยันการทำรายการทั้งหมด
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            // ดักจับ Error และยกเลิกสิ่งที่ลบไปหากเกิดข้อผิดพลาด
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error deleting VenCommand: " . $e->getMessage());
            return false;
        }
    }

    // อัปเดตสถานะคำสั่งเวร (1=กำลังดำเนินการ, 2=ยืนยันแล้ว)
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

    // 1. ดึงข้อมูลเวรทั้งหมดในเดือนที่เลือก เพื่อเอาไปลงปฏิทิน (Global View)
    public function getSchedulesByMonth($year_month) {
        $query = "SELECT s.id, s.ven_date as date, DAY(s.ven_date) as day, 
                         s.user_id, CONCAT(p.prefix_name, ' ', p.first_name, ' ', p.last_name) as user_name,
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
                  
                  -- 🌟 แก้ไขการเรียงลำดับ (ORDER BY) ตรงนี้ครับ
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

    // อัปเดตลำดับ (srt) ของหน้าที่ย่อย
    public function updateSubDutyOrder($items) {
        $this->conn->beginTransaction();
        try {
            $query = "UPDATE ven_name_sub SET srt = :srt WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            foreach ($items as $item) {
                $stmt->execute([
                    ':srt' => $item['srt'],
                    ':id' => $item['id']
                ]);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // 1. ดึงตารางเวรของตัวเอง (เฉพาะคำสั่งที่ยืนยันแล้ว status=2)
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
            // ดึงข้อมูลคำขอ
            $stmt = $this->conn->prepare("SELECT * FROM ven_change WHERE id = ?");
            $stmt->execute([$change_id]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            // สลับ user_id ในตารางเวรหลัก
            $upd1 = $this->conn->prepare("UPDATE ven_schedule SET user_id = ? WHERE id = ?");
            $upd1->execute([$req['user2_id'], $req['s1_id']]); // เอาเพื่อนมาลงเวรเรา
            
            $upd2 = $this->conn->prepare("UPDATE ven_schedule SET user_id = ? WHERE id = ?");
            $upd2->execute([$req['user1_id'], $req['s2_id']]); // เอาเราไปลงเวรเพื่อน

            // อัปเดตสถานะคำขอเป็น 1 (ยอมรับแล้ว)
            $this->conn->prepare("UPDATE ven_change SET status = 1 WHERE id = ?")->execute([$change_id]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

// โอนเวรให้ผู้อื่นโดยตรง
    
    // โอนเวรให้ผู้อื่นโดยตรง พร้อมบันทึกประวัติ
    // โอนเวรให้ผู้อื่นโดยตรง พร้อมรันเลขที่เอกสาร
    public function transferShift($schedule_id, $new_user_id) {
        try {


            // 🌟 1. ดึงข้อมูลเวรเพื่อตรวจสอบวันที่ก่อน
            $stmtCheck = $this->conn->prepare("SELECT user_id, ven_date FROM ven_schedule WHERE id = :id");
            $stmtCheck->execute([':id' => $schedule_id]);
            $shift = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$shift) {
                error_log("Transfer Error: ไม่พบเวรที่ต้องการเปลี่ยน");
                return false;
            }

            // 🌟 2. ดึงการตั้งค่าจากฐานข้อมูลว่า "อนุญาตให้เปลี่ยนย้อนหลังหรือไม่"
            $stmtSetting = $this->conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'allow_retro_transfer'");
            $stmtSetting->execute();
            $settingRow = $stmtSetting->fetch(PDO::FETCH_ASSOC);
            $allow_retro_transfer = ($settingRow && $settingRow['setting_value'] === '1'); // ถ้าเป็น '1' คือ true
            

            // 🌟 3. ตรวจสอบเวรย้อนหลัง (ทำงานก็ต่อเมื่อแอดมิน "ปิด" สวิตช์อนุญาตย้อนหลังเอาไว้)
            if (!$allow_retro_transfer) {
                // ถ้าวันที่เวร น้อยกว่า วันที่ปัจจุบัน แปลว่าผ่านมาแล้ว
                if (strtotime($shift['ven_date']) < strtotime(date('Y-m-d'))) {
                    error_log("Transfer Error: พยายามเปลี่ยนเวรย้อนหลัง ID: " . $schedule_id . " (ระบบไม่อนุญาต)");
                    return false; 
                }
            }



            $this->conn->beginTransaction();

            // 1. ดึง ID ของเจ้าของเวรคนเดิม
            $stmtOld = $this->conn->prepare("SELECT user_id FROM ven_schedule WHERE id = :id");
            $stmtOld->execute([':id' => $schedule_id]);
            $old_user = $stmtOld->fetch(PDO::FETCH_ASSOC);
            $old_user_id = $old_user ? $old_user['user_id'] : null;

            // 2. อัปเดตตารางหลัก (ถ้าตกลงกันว่าต้องรออนุมัติก่อนถึงจะเปลี่ยนชื่อ อาจจะต้องข้ามขั้นตอนนี้ไปทำตอนอนุมัติ แต่ถ้าให้เปลี่ยนชื่อเลยก็ใช้โค้ดนี้ได้ครับ)
            $queryUpdate = "UPDATE ven_schedule SET user_id = :new_user_id WHERE id = :schedule_id";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->execute([
                ':new_user_id' => $new_user_id,
                ':schedule_id' => $schedule_id
            ]);

            // 3. รันเลขที่เอกสารใบเปลี่ยนเวร (Running Number)
            $yearMonth = date("ym"); // จะได้ปีเดือน เช่น 2404 (ปี 2024 เดือน 4)
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
            // นำมาประกอบกัน จะได้รูปแบบ VC-2404-001
            $new_change_no = $prefix . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

            // 4. บันทึกลงตาราง ven_change (กำหนดสถานะ = 0 คือ รออนุมัติ)
            if ($old_user_id) {
                // หากตาราง ven_change ไม่มีคอลัมน์ created_at ให้ลบ created_at และ NOW() ออกนะครับ
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
            $this->conn->rollBack();
            error_log("Transfer Shift Error: " . $e->getMessage());
            return false;
        }
    }

    // เพิ่มฟังก์ชันนี้ใน Model เพื่อดึงประวัติการเปลี่ยนทั้งหมด


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
                     CONCAT_WS(' ', p1.prefix_name, p1.first_name, p1.last_name) AS user1_name,
                     CONCAT_WS(' ', p2.prefix_name, p2.first_name, p2.last_name) AS user2_name
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

        // 1. ดึงข้อมูลใบเปลี่ยนเวรออกมาก่อน เพื่อดูว่าใครแลกกับใคร
        $stmtChange = $this->conn->prepare("SELECT s1_id, user1_id, user2_id FROM ven_change WHERE id = :id");
        $stmtChange->execute([':id' => $id]);
        $changeData = $stmtChange->fetch(PDO::FETCH_ASSOC);

        if (!$changeData) {
            throw new Exception("ไม่พบข้อมูลใบเปลี่ยนเวร");
        }

        // 2. อัปเดตสถานะในตาราง ven_change
        $query = "UPDATE ven_change SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':status' => $status, ':id' => $id]);

        // 3. ปรับปรุงชื่อผู้เข้าเวรในตารางหลัก (ven_schedule) ให้ตรงกับสถานะ
        // - ถ้าสถานะเป็น 2 (ไม่อนุมัติ) -> คืนเวรให้คนเดิม (user1_id)
        // - ถ้าสถานะเป็น 1 หรือ 0 (อนุมัติ/รออนุมัติ) -> ให้สิทธิ์คนใหม่ (user2_id)
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

    // 1. ดึงข้อมูลการตั้งค่า Telegram
    public function getTelegramSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM telegram_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching Telegram settings: " . $e->getMessage());
            return false;
        }
    }

    // 2. อัปเดตข้อมูลการตั้งค่า Telegram
    public function updateTelegramSettings($data) {
    try {
        // 1. เริ่ม Transaction (ถ้ามี Error ตรงไหน จะได้ยกเลิกการบันทึกทั้งหมด ป้องกันข้อมูลแหว่ง)
        $this->conn->beginTransaction();
        
        $sql = "UPDATE telegram_settings SET 
                    bot_token = :bot_token, 
                    chat_id = :chat_id, 
                    notify_confirmed = :notify_confirmed, 
                    notify_change_request = :notify_change_request, 
                    notify_approval = :notify_approval 
                WHERE id = 1";
        
        $stmt = $this->conn->prepare($sql);
        
        // 2. 🌟 เอาคำว่า return ออกจากตรงนี้ เพื่อให้โค้ดบรรทัดถัดไปทำงานต่อได้
        $stmt->execute([
            ':bot_token' => $data['bot_token'],
            ':chat_id' => $data['chat_id'],
            ':notify_confirmed' => $data['notify_confirmed'] ? 1 : 0,
            ':notify_change_request' => $data['notify_change_request'] ? 1 : 0,
            ':notify_approval' => $data['notify_approval'] ? 1 : 0
        ]);

        // 3. จัดการตารางเวลาแจ้งเตือน
        $this->conn->prepare("DELETE FROM telegram_notify_times")->execute();
        if (!empty($data['notify_times'])) {
            $stmtTime = $this->conn->prepare("INSERT INTO telegram_notify_times (send_time, notify_day, status) VALUES (:t, :d, :s)");
            foreach ($data['notify_times'] as $timeSlot) {
                $stmtTime->execute([
                    ':t' => $timeSlot['send_time'],
                    ':d' => $timeSlot['notify_day'], // 🌟 บันทึกค่าที่เลือก (0 หรือ 1)
                    ':s' => $timeSlot['status'] ? 1 : 0
                ]);
            }
        }
        
        // 4. บันทึกข้อมูลทั้งหมดลงฐานข้อมูล (Commit) และจบฟังก์ชัน
        $this->conn->commit();
        return true;

    } catch (PDOException $e) {
        // ถ้ายกเลิก หรือมี Error ตรงไหน ให้ Rollback (ย้อนกลับ) สิ่งที่ทำไปใน Transaction นี้ทั้งหมด
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        error_log("Error updating Telegram settings: " . $e->getMessage());
        return false;
    }
}

}