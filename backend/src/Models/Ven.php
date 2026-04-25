<?php
// backend/src/Models/Ven.php

class Ven {
    private $conn;
    private $table_name = "ven";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ฟังก์ชันดึงข้อมูลตารางเวร (สามารถกรองตามเดือน-ปี ได้)
    public function getVenList($monthYear = null) {
        // ใช้ LEFT JOIN เพื่อดึงชื่อผู้ใช้และข้อมูลประเภทเวร
        $query = "SELECT 
                    v.id, 
                    v.ven_date, 
                    v.ven_time, 
                    v.status,
                    p.name AS first_name, 
                    p.sname AS last_name,
                    vns.name AS role_name,
                    vns.color
                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN ven_name_sub vns ON v.vns_id = vns.id";

        // ถ้ามีการส่งเดือนปีมา (เช่น '2026-04') ให้กรองข้อมูล
        if ($monthYear != null) {
            $query .= " WHERE DATE_FORMAT(v.ven_date, '%Y-%m') = :monthYear";
        }

        $query .= " ORDER BY v.ven_date ASC, v.ven_time ASC";

        $stmt = $this->conn->prepare($query);

        if ($monthYear != null) {
            $stmt->bindParam(":monthYear", $monthYear);
        }

        $stmt->execute();
        return $stmt;
    }

    // ดึงรายละเอียดเวรแบบเจาะจง 1 รายการ (สำหรับหน้า Pop-up)
    public function getVenDetail($id) {
        $query = "SELECT 
                    v.id AS ven_id,
                    v.ven_date,
                    v.ven_time,
                    v.status,
                    p.user_id,
                    p.fname,
                    p.name AS first_name,
                    p.sname AS last_name,
                    p.img,
                    vns.name AS role_name,
                    vns.price,
                    vns.color,
                    vn.name AS main_duty_name,
                    vc.ven_com_num
                  FROM " . $this->table_name . " v
                  LEFT JOIN profile p ON v.user_id = p.user_id
                  LEFT JOIN ven_name_sub vns ON v.vns_id = vns.id
                  LEFT JOIN ven_name vn ON v.vn_id = vn.id
                  LEFT JOIN ven_com vc ON v.ven_com_id = vc.id
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