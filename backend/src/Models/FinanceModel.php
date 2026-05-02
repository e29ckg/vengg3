<?php
// backend/src/Models/FinanceModel.php

class FinanceModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ฟังก์ชันดึงรายการคำสั่ง
    public function getCommandsByMonth($monthYear) {
        $sql = "SELECT 
                    vc.id, 
                    vn.name as name,
                    DATE_FORMAT(vc.com_date, '%Y-%m-%d') as com_date,
                    vc.com_num as com_num
                FROM ven_com vc
                LEFT JOIN ven_name vn ON vn.id = vc.ven_name_id
                WHERE vc.ven_month = :month
                ORDER BY com_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':month', $monthYear);
        $stmt->execute();
        
        return $stmt; // คืนค่าเป็น Statement กลับไปให้ Controller เช็ค rowCount
    }

    // ฟังก์ชันดึงข้อมูลสรุปการเงิน
    public function getFinanceReportByMonth($month, $command_id = null) {
        $sql = "SELECT 
                    p.prefix_name, p.first_name, p.last_name, p.dep,
                    COUNT(v.id) as total_days,
                    vns.price as rate_per_day, 
                    GROUP_CONCAT(DAY(v.ven_date) ORDER BY v.ven_date ASC SEPARATOR ', ') as work_dates,
                    '' as remark
                FROM ven_schedule v 
                JOIN profile p ON v.user_id = p.user_id 
                JOIN ven_com vc ON v.ven_com_id = vc.id 
                JOIN ven_name_sub vns ON v.ven_name_sub_id = vns.id
                JOIN ven_name vn ON vns.ven_name_id = vn.id 

                WHERE v.ven_com_id = :command_id AND DATE_FORMAT(v.ven_date, '%Y-%m') = :month         
                
                -- เพิ่ม p.dep และ vns.price เข้าไปใน GROUP BY ตามแบบที่ 1
                GROUP BY p.user_id, p.first_name, p.last_name, p.dep, vns.price
                 
                ORDER BY p.user_id ASC";

        $stmt = $this->db->prepare($sql);        
        $stmt->bindParam(':command_id', $command_id);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        return $stmt; // คืนค่ากลับไปให้ Controller
    }

    // ฟังก์ชันใหม่: ดึงวันที่เป็น "เวรกลางวัน" (เพื่อหาว่าเป็นวันหยุด)
   public function getHolidaysFromDayShift($month, $command_id = null) {
        $sql = "SELECT DISTINCT DAY(s.ven_date) as h_day 
                FROM ven_schedule s
                JOIN ven_com vc ON s.ven_com_id = vc.id 
                JOIN ven_name_sub vns ON s.ven_name_sub_id = vns.id
                JOIN ven_name vn ON vns.ven_name_id = vn.id
                
                -- กรองเดือนและคำว่า 'กลางวัน'
                WHERE DATE_FORMAT(s.ven_date, '%Y-%m') = :month
                AND vn.dn LIKE 'กลางวัน%'"; 

        // สร้าง Array เก็บพารามิเตอร์เริ่มต้น (มีเดือนแน่นอน)
        $params = [':month' => $month];
               
        $sql .= " ORDER BY h_day ASC";

        $stmt = $this->db->prepare($sql);        
        
        // โยน $params เข้าไปทีเดียว โค้ดจะสะอาดและไม่เกิด Error ทับซ้อน
        $stmt->execute($params);
        
        // คืนค่ากลับไปเป็น Array ของตัวเลขวันที่เลย เช่น [1, 2, 8, 9]
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }

    
}
?>