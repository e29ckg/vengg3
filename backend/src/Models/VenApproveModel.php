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
        if (!in_array((string)$status, ['0','1'], true)) return ['success'=>false,'error'=>'สถานะไม่ถูกต้อง','code'=>400];
        try {
            $this->conn->beginTransaction();
            $request = $this->conn->prepare('SELECT * FROM ven_change WHERE id = ?');
            $request->execute([$change_id]);
            $change = $request->fetch(PDO::FETCH_ASSOC);
            if (!$change) throw new RuntimeException('ไม่พบคำขอ');
            $ids = [$change['s1_id']];
            if ((int)$change['is_swap'] === 1) $ids[] = $change['s2_id'];
            sort($ids, SORT_NUMERIC);
            $schedules = [];
            $query = $this->conn->prepare('SELECT id, user_id, status FROM ven_schedule WHERE id = ? FOR UPDATE');
            foreach ($ids as $id) {
                $query->execute([$id]);
                $schedules[(string)$id] = $query->fetch(PDO::FETCH_ASSOC);
            }
            $locked = $this->conn->prepare('SELECT * FROM ven_change WHERE id = ? FOR UPDATE');
            $locked->execute([$change_id]);
            $current = $locked->fetch(PDO::FETCH_ASSOC);
            if (!$current || !in_array((int)$current['status'], [0,1], true) || $current['s1_id'] != $change['s1_id'] || $current['s2_id'] != $change['s2_id']) throw new RuntimeException('คำขอเปลี่ยนไปแล้วหรือถูกยกเลิก');
            $later = $this->conn->prepare('SELECT id FROM ven_change WHERE id > ? AND status IN (0,1) AND (s1_id = ? OR s2_id = ?) LIMIT 1');
            foreach ($ids as $id) {
                $schedule = $schedules[(string)$id];
                $expectedOwner = $id == $change['s1_id'] ? $change['user2_id'] : $change['user1_id'];
                if (!$schedule || (string)$schedule['user_id'] !== (string)$expectedOwner || !in_array((int)$schedule['status'], [1,2], true)) throw new RuntimeException('ตารางเวรเปลี่ยนไปแล้ว');
                $later->execute([$change_id,$id,$id]);
                if ($later->fetchColumn()) throw new RuntimeException('มีคำขอใหม่กว่าในเวรนี้');
            }
            $update = $this->conn->prepare('UPDATE ven_schedule SET status = ? WHERE id = ?');
            foreach ($ids as $id) $update->execute([(int)$status === 1 ? 1 : 2,$id]);
            $this->conn->prepare('UPDATE ven_change SET status = ? WHERE id = ?')->execute([$status,$change_id]);
            $this->conn->commit();
            return ['success'=>true];
        } catch (PDOException $error) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Approval database operation failed');
            return ['success'=>false,'error'=>'ไม่สามารถอัปเดตการอนุมัติได้','code'=>500];
        } catch (RuntimeException $error) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['success'=>false,'error'=>'ไม่สามารถเปลี่ยนสถานะคำขอที่ตารางเวรเปลี่ยนไปหรือถูกยกเลิกแล้ว','code'=>409];
        } catch (Throwable $error) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Approval update failed');
            return ['success'=>false,'error'=>'ไม่สามารถอัปเดตการอนุมัติได้','code'=>500];
        }
    }
}
