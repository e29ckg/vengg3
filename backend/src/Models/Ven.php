<?php
// backend/src/Models/Ven.php

class Ven {
    private $conn;
    private $table_name = "ven_schedule";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================
    // ฟังก์ชันดึงข้อมูลตารางเวร (แสดงใน FullCalendar)
    // ==========================================
    public function getVenList($monthYear = null) {
        // 🌟 ปรับ Query ให้คืนค่าตรงกับรูปแบบที่ FullCalendar ต้องการ (id, title, date, backgroundColor)
        $query = "SELECT 
                    v.id, 
                    v.user_id,
                    v.ven_date AS date, 
                    CONCAT(p.prefix_name, p.first_name, ' ', p.last_name) AS title,
                    vns.color AS backgroundColor,
                    IF(vn.dn LIKE '%กลางคืน%', '16:30:00', '08:30:00') AS ven_time
                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN ven_name_sub vns ON v.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vc ON v.ven_com_id = vc.id
                  LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id";

        // กรองตามเดือน-ปี (รูปแบบ YYYY-MM)
        if ($monthYear != null) {
            $query .= " WHERE DATE_FORMAT(v.ven_date, '%Y-%m') = :monthYear";
        }

        $query .= " ORDER BY v.ven_date ASC, vn.srt ASC, vns.srt ASC, v.id ASC"; // เรียงตามวันที่ และเวลาของเวร (เช้า-กลางคืน)

        $stmt = $this->conn->prepare($query);

        if ($monthYear != null) {
            $stmt->bindParam(":monthYear", $monthYear);
        }

        $stmt->execute();
        return $stmt;
    }

    // ==========================================
    // ดึงรายละเอียดเวรแบบเจาะจง 1 รายการ (สำหรับหน้า Pop-up โอนเวร)
    // ==========================================
    public function getVenDetail($id) {
        $query = "SELECT 
                    v.id AS ven_id,
                    v.ven_date,
                    v.status,
                    vc.status AS com_status,
                    IF(vn.dn LIKE '%กลางคืน%', '16:30:00', '08:30:00') AS ven_time,
                    p.user_id,                    
                    
                    CONCAT_WS(' ', CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, '')), p.last_name) AS full_name,
                    p.img  AS profile_image,
                    vns.id AS sub_id,
                    vns.name AS duty_role,
                    vns.price,
                    vns.color,
                    vn.name AS duty_main,
                    vc.com_num AS command_num,

                    -- 🌟 ข้อมูลการเปลี่ยนเวร
                    vch.id AS change_id,
                    CONCAT_WS(' ', CONCAT(IFNULL(p1.prefix_name, ''), IFNULL(p1.first_name, '')), p1.last_name) AS user1_name,
                    CONCAT_WS(' ', CONCAT(IFNULL(p2.prefix_name, ''), IFNULL(p2.first_name, '')), p2.last_name) AS user2_name

                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN ven_name_sub vns ON v.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vc ON v.ven_com_id = vc.id
                  LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id
                  
                  -- 🌟 แก้ไข: บังคับให้ดึงเฉพาะ 'ใบเปลี่ยนเวรล่าสุด' ของเวรนี้เท่านั้น
                  LEFT JOIN ven_change vch ON vch.id = (
                      SELECT MAX(id) FROM ven_change 
                      WHERE (s1_id = v.id OR s2_id = v.id) AND status = 1
                  )
                  LEFT JOIN profile p1 ON vch.user1_id = p1.user_id
                  LEFT JOIN profile p2 ON vch.user2_id = p2.user_id
                  
                  WHERE v.id = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(":id", $id);
        
        $stmt->execute();
        return $stmt;
    }

    
public function getChangeHistory($ven_id) {
    $query = "SELECT 
                vch.id AS change_id,
                vch.change_no,
                vch.status,
                vch.created_at AS change_date, -- วันที่ทำรายการ
                vch.user1_id, -- 🌟 สำคัญ: ดึงไอดีคนโอน (เพื่อใช้เช็คสิทธิ์ปุ่มยกเลิก)
                vch.user2_id,
                CONCAT_WS(' ', CONCAT(IFNULL(p1.prefix_name, ''), IFNULL(p1.first_name, '')), p1.last_name) AS user1_name,
                CONCAT_WS(' ', CONCAT(IFNULL(p2.prefix_name, ''), IFNULL(p2.first_name, '')), p2.last_name) AS user2_name
              FROM ven_change vch
              LEFT JOIN profile p1 ON vch.user1_id = p1.user_id
              LEFT JOIN profile p2 ON vch.user2_id = p2.user_id
              WHERE (vch.s1_id = :id OR vch.s2_id = :id)
              ORDER BY vch.id DESC"; // 🌟 ล่าสุดอยู่บนสุด
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $ven_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function getDetail($id) {
    $query = "SELECT s.*, 
                p.prefix_name, p.first_name, p.last_name,
                CONCAT(p.prefix_name, p.first_name, ' ', p.last_name) as full_name,
                sub.name as duty_role, sub.color, sub.price,
                n.name as duty_main,
                c.com_num as command_num,
                /* 🌟 เพิ่มส่วนนี้เพื่อดึงข้อมูลใบเปลี่ยน */
                vc.id as change_id,
                u1.id as user1_id, p1.first_name as user1_name,
                u2.id as user2_id, p2.first_name as user2_name
              FROM ven_schedule s
              JOIN user u ON s.user_id = u.id
              JOIN profile p ON u.id = p.user_id
              JOIN ven_name_sub sub ON s.ven_name_sub_id = sub.id
              JOIN ven_com c ON s.ven_com_id = c.id
              JOIN ven_name n ON c.ven_name_id = n.id
              /* 🌟 Join ตารางใบเปลี่ยนเวร */
              LEFT JOIN ven_change vc ON (vc.s1_id = s.id OR vc.s2_id = s.id) AND vc.status = 1
              LEFT JOIN user u1 ON vc.user1_id = u1.id
              LEFT JOIN profile p1 ON u1.id = p1.user_id
              LEFT JOIN user u2 ON vc.user2_id = u2.id
              LEFT JOIN profile p2 ON u2.id = p2.user_id
              WHERE s.id = :id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 🌟 ฟังก์ชันยกเลิกใบเปลี่ยนเวรที่ยังไม่อนุมัติ
    public function cancelShiftChange($change_id) {
        try {
            $this->conn->beginTransaction();

            // 1. ตรวจสอบว่าใบเปลี่ยนนี้มีอยู่จริง และมีสถานะ "รออนุมัติ" (status = 0)
            $stmt = $this->conn->prepare("SELECT s1_id, user1_id FROM ven_change WHERE id = :id AND status = 0");
            $stmt->execute([':id' => $change_id]);
            $change = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$change) {
                // ถ้าไม่เจอข้อมูล หรือสถานะไม่เป็น 0 (อาจจะอนุมัติไปแล้ว)
                $this->conn->rollBack();
                return false; 
            }

            // 2. ดึงเวรคืนให้คนก่อนหน้า (อัปเดต user_id กลับไปเป็น user1_id)
            $stmtUpdate = $this->conn->prepare("UPDATE ven_schedule SET user_id = :user1_id WHERE id = :schedule_id");
            $stmtUpdate->execute([
                ':user1_id' => $change['user1_id'],
                ':schedule_id' => $change['s1_id']
            ]);

            // 3. ลบใบเปลี่ยนเวรที่ถูกยกเลิกทิ้ง เพื่อทำความสะอาดประวัติ
            $stmtDelete = $this->conn->prepare("DELETE FROM ven_change WHERE id = :id");
            $stmtDelete->execute([':id' => $change_id]);

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Cancel Change Error: " . $e->getMessage());
            return false;
        }
    }


}
?>