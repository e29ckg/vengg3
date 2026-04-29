<?php
// backend/src/Models/Report.php

class Report  {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getVenCommands($monthYear = null) {
        $query =   "SELECT vc.id, vc.com_num AS ven_com_num, vc.com_date AS ven_com_date, vn.name AS ven_com_name, vn.srt AS srt 
                    FROM ven_com AS vc
                    LEFT JOIN ven_name AS vn ON vc.ven_name_id = vn.id
                    WHERE vc.ven_month = :monthYear
                    ORDER BY vn.srt ASC, vc.com_num ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":monthYear", $monthYear );
        $stmt->execute();
        return $stmt;
    }

    public function getSchedulesByCommandId($commandId) {
        // Query นี้สมมติว่าตารางชื่อ ven_schedule และมีการ JOIN กับ users เพื่อเอาชื่อ 
        // และ JOIN กับ ven_name เพื่อเอาชื่อกลุ่มเวร
        $query = "
            SELECT 
                s.*,
                CONCAT( p.prefix_name, p.first_name, ' ', p.last_name) AS full_name,
                p.dep AS dep,
                vn.name AS ven_name,
                vn.srt AS ven_srt,
                vns.name AS sub_name,
                vns.srt AS sub_srt
            FROM ven_schedule s
            LEFT JOIN profile p ON p.user_id = s.user_id  
            LEFT JOIN ven_com vc ON vc.id = s.ven_com_id
            LEFT JOIN ven_name vn ON vn.id = vc.ven_name_id
            LEFT JOIN ven_name_sub vns ON vns.id = s.ven_name_sub_id          
            WHERE s.ven_com_id = :commandId 
            ORDER BY vns.srt ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':commandId', $commandId);
        $stmt->execute();
        
        return $stmt;
    }
  

    public function getVenSubReportByCommandId($commandId) {
        $query = "SELECT 
                    vc.id AS command_id,
                    vc.ven_name_id AS ven_name_id,
                    vn.name AS ven_name,
                    vns.name AS sub_name
                  FROM ven_com vc
                  left join ven_name vn ON vc.ven_name_id = vn.id
                  LEFT JOIN ven_name_sub vns ON vc.ven_name_id = vns.ven_name_id
                  WHERE vc.id = :commandId
                  order by vns.srt ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':commandId', $commandId);
        $stmt->execute();
        
        return $stmt;
    }


    // ฟังก์ชันพิเศษสำหรับดึงข้อมูลหน่วยงาน (ดึงแค่แถวแรก)
    public function getAgencyConfig() {
        $query = "SELECT * FROM agency_config WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
