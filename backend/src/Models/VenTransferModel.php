<?php
class VenTransferModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function performTransfer($currentUserId, $s1_id, $user2_id, $is_swap, $s2_id) {
        try {
            $this->conn->beginTransaction(); // 🌟 เริ่ม Transaction ป้องกันข้อมูลพัง

            // 1. เช็คว่าเวรนี้กำลังรออนุมัติเปลี่ยนเวรอยู่หรือไม่
            $stmtCheck = $this->conn->prepare("SELECT id FROM ven_change WHERE (s1_id = ? OR s2_id = ?) AND status = 0");
            $stmtCheck->execute([$s1_id, $s1_id]);
            if ($stmtCheck->fetch()) {
                $this->conn->rollBack();
                return ['success' => false, 'error' => 'เวรนี้อยู่ระหว่างดำเนินการรออนุมัติอยู่แล้ว', 'code' => 400];
            }

            // 2. ดึงข้อมูลวันที่ ($date1, $date2) ล่วงหน้า เพื่อเตรียมใช้อัปเดตปฏิทิน Google
            $stmtDate1 = $this->conn->prepare("SELECT ven_date FROM ven_schedule WHERE id = ?");
            $stmtDate1->execute([$s1_id]);
            $date1 = $stmtDate1->fetchColumn();

            $date2 = null;
            if ($is_swap == 1 && $s2_id) {
                $stmtDate2 = $this->conn->prepare("SELECT ven_date FROM ven_schedule WHERE id = ?");
                $stmtDate2->execute([$s2_id]);
                $date2 = $stmtDate2->fetchColumn();
            }

            // 3. รันเลขที่ใบเปลี่ยนเวร
            $changeNo = "CH-" . date('Ym') . "-" . rand(1000, 9999);

            // 4. บันทึกคำขอลงตาราง ven_change
            $sql = "INSERT INTO ven_change (change_no, s1_id, user1_id, user2_id, is_swap, s2_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([ $changeNo, $s1_id, $currentUserId, $user2_id, $is_swap, ($is_swap == 1) ? $s2_id : null ]);

            // 5. ย้ายชื่อในตารางเวร และปรับสถานะเป็น 2 ทันที!
            $tableName = "ven_schedule"; 
            if ($is_swap == 1) {
                // กรณีสลับเวร (เปลี่ยนชื่อทั้ง 2 วัน และปรับสถานะ=2)
                $stmt1 = $this->conn->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                $stmt1->execute([$user2_id, $s1_id]);
                
                $stmt2 = $this->conn->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                $stmt2->execute([$currentUserId, $s2_id]);
            } else {
                // กรณียกให้ (โอนขาด) (เปลี่ยนชื่อแค่เวรเดียว และปรับสถานะ=2)
                $stmt1 = $this->conn->prepare("UPDATE $tableName SET user_id = ?, status = 2 WHERE id = ?");
                $stmt1->execute([$user2_id, $s1_id]);
            }

            $this->conn->commit(); // 🌟 บันทึก Transaction สำเร็จ

            // ส่งคืนข้อมูลวันที่กลับไป เพื่อให้ Controller นำไปอัปเดต Google Calendar
            return [
                'success' => true, 
                'change_no' => $changeNo,
                'date1' => $date1,
                'date2' => $date2
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack(); // ถ้ายกเวรพัง ให้ Rollback ข้อมูลกลับ
            return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage(), 'code' => 500];
        }
    }
}