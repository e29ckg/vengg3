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
                    v.ven_date AS date, 
                    CONCAT(f.name, p.name, ' ', p.sname) AS title,
                    vns.color AS backgroundColor,
                    IF(vn.dn LIKE '%กลางคืน%', '16:30:00', '08:30:00') AS ven_time
                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN fname f ON p.fname_id = f.id
                  LEFT JOIN ven_name_sub vns ON v.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vc ON v.ven_com_id = vc.id
                  LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id";

        // กรองตามเดือน-ปี (รูปแบบ YYYY-MM)
        if ($monthYear != null) {
            $query .= " WHERE DATE_FORMAT(v.ven_date, '%Y-%m') = :monthYear";
        }

        $query .= " ORDER BY v.ven_date ASC";

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
        // 🌟 ปรับชื่อตัวแปรให้ตรงกับฝั่ง Vue.js (selectedVen)
        $query = "SELECT 
                    v.id AS ven_id,
                    v.ven_date,
                    IF(vn.dn LIKE '%กลางคืน%', '16:30:00', '08:30:00') AS ven_time,
                    p.user_id,
                    CONCAT(f.name, p.name, ' ', p.sname) AS full_name,
                    '' AS profile_image,
                    vns.id AS sub_id,
                    vns.name AS duty_role,
                    vns.price,
                    vns.color,
                    vn.name AS duty_main,
                    vc.com_num AS command_num
                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN fname f ON p.fname_id = f.id
                  LEFT JOIN ven_name_sub vns ON v.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vc ON v.ven_com_id = vc.id
                  LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id
                  WHERE v.id = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        
        // กรองข้อมูลรหัสให้ปลอดภัย
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(":id", $id);
        
        $stmt->execute();
        return $stmt;
    }
}
?>