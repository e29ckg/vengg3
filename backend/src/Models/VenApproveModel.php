<?php
class VenApproveModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🌟 1. ดึงรายการคำขอทั้งหมด
    public function getChangeRequests() {
        $query = "SELECT 
                    vc.id, vc.change_no, vc.status, vc.is_swap, vc.created_at,
                    vc.user1_id, vc.user2_id,
                    CONCAT_WS(' ', CONCAT(IFNULL(p1.prefix_name, ''), IFNULL(p1.first_name, '')), p1.last_name) AS user1_name,
                    CONCAT_WS(' ', CONCAT(IFNULL(p2.prefix_name, ''), IFNULL(p2.first_name, '')), p2.last_name) AS user2_name,
                    vs1.ven_date AS s1_date, vs2.ven_date AS s2_date, 
                    vns.name AS duty_role, vn.name AS duty_main, vn.name_full AS duty_main_full
                  FROM ven_change vc
                  LEFT JOIN profile p1 ON vc.user1_id = p1.user_id
                  LEFT JOIN profile p2 ON vc.user2_id = p2.user_id
                  LEFT JOIN ven_schedule vs1 ON vc.s1_id = vs1.id
                  LEFT JOIN ven_schedule vs2 ON vc.s2_id = vs2.id
                  LEFT JOIN ven_name_sub vns ON vs1.ven_name_sub_id = vns.id
                  LEFT JOIN ven_com vcom ON vs1.ven_com_id = vcom.id
                  LEFT JOIN ven_name vn ON vcom.ven_name_id = vn.id
                  ORDER BY vc.status ASC, vc.created_at DESC";
        
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🌟 2. อัปเดตสถานะการอนุมัติ (ใช้ Transaction ป้องกันข้อผิดพลาด)
    public function forceUpdateStatus($change_id, $status) {
        try {
            // เริ่มการทำงานแบบ Transaction (ถ้า Error จะย้อนกลับข้อมูลทั้งหมด ไม่บันทึกครึ่งๆ กลางๆ)
            $this->conn->beginTransaction();

            // ดึงข้อมูลใบคำขอ
            $stmt = $this->conn->prepare("SELECT * FROM ven_change WHERE id = ?");
            $stmt->execute([$change_id]);
            $changeReq = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$changeReq) {
                $this->conn->rollBack();
                return ['success' => false, 'error' => 'ไม่พบข้อมูลใบคำขอนี้', 'code' => 404];
            }

            // อัปเดตตาราง ven_schedule
            $tableName = "ven_schedule";
            if ($changeReq['is_swap'] == 1) {
                $stmt1 = $this->conn->prepare("UPDATE $tableName SET status = ? WHERE id = ?");
                $stmt1->execute([$status, $changeReq['s1_id']]);
                $stmt2 = $this->conn->prepare("UPDATE $tableName SET status = ? WHERE id = ?");
                $stmt2->execute([$status, $changeReq['s2_id']]);
            } else {
                $stmt1 = $this->conn->prepare("UPDATE $tableName SET status = ? WHERE id = ?");
                $stmt1->execute([$status, $changeReq['s1_id']]);
            }

            // อัปเดตตาราง ven_change
            $stmtUpdate = $this->conn->prepare("UPDATE ven_change SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$status, $change_id]);

            // ยืนยันการบันทึก
            $this->conn->commit();
            return ['success' => true];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage(), 'code' => 500];
        }
    }
}