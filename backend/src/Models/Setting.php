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
    public function getAgencyConfig() {
        $query = "SELECT * FROM `agency_config` LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAgencyConfig($name, $address) {
        $query = "UPDATE `agency_config` SET label = :label, val = :val WHERE id = 1"; // ปรับตามโครงสร้างตารางจริงของคุณ
        // หากตารางของคุณใช้ชื่อฟิลด์อื่น เช่น name ให้ปรับที่นี่ครับ
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':label', $name);
        $stmt->bindParam(':val', $address);
        return $stmt->execute();
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
        $query = "UPDATE `ven_name` SET srt = :srt, name = :name, DN = :dn, name_full = :name_full WHERE id = :id";
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
        $query = "SELECT vu.id as vu_id, vu.order_num, u.id as user_id, p.prefix_name, p.first_name, p.last_name
                  FROM ven_user vu
                  JOIN user u ON vu.user_id = u.id
                  JOIN profile p ON u.id = p.user_id
                  WHERE vu.ven_name_sub_id = :sub_id
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
                  VALUES (:com_num, :com_date, :ven_month, :ven_name_id, 1, :ven_com_days)";
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
        $query = "DELETE FROM ven_com WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
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
        $query = "INSERT INTO ven_schedule (ven_date, ven_com_id, ven_name_sub_id, user_id) 
                  VALUES (:ven_date, :com_id, :sub_id, :user_id)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':ven_date' => $data['date'],
            ':com_id'   => $data['com_id'],
            ':sub_id'   => $data['sub_id'],
            ':user_id'  => $data['user_id']
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
    public function transferShift($schedule_id, $new_user_id) {
        $query = "UPDATE ven_schedule SET user_id = :new_user WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':new_user' => $new_user_id,
            ':id' => $schedule_id
        ]);
    }
    
}