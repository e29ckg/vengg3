<?php
class VenTransferModel {
    private $conn;

    public function __construct($db) { $this->conn = $db; }

    private function reject($message, $code) {
        $this->conn->rollBack();
        return ['success' => false, 'error' => $message, 'code' => $code];
    }

    private function lockSchedules($ids) {
        sort($ids, SORT_NUMERIC);
        $stmt = $this->conn->prepare('SELECT id, user_id, ven_name_sub_id, ven_date, status FROM ven_schedule WHERE id = ? FOR UPDATE');
        $schedules = [];
        foreach ($ids as $id) {
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            $schedules[(string)$id] = $row;
        }
        return $schedules;
    }

    public function performTransfer($currentUserId, $s1_id, $user2_id, $is_swap, $s2_id) {
        try {
            $this->conn->beginTransaction();
            if (!in_array($is_swap, [0, 1], true) || ($is_swap === 1 && !$s2_id) || ($is_swap === 0 && $s2_id))
                return $this->reject('ข้อมูลการเปลี่ยนเวรไม่ถูกต้อง', 400);
            if ((string)$currentUserId === (string)$user2_id || ($is_swap === 1 && (string)$s1_id === (string)$s2_id))
                return $this->reject('ไม่สามารถเปลี่ยนเวรกับตนเองหรือเวรเดิมได้', 400);

            // Lock schedules before checking ownership and pending requests.
            $schedules = $this->lockSchedules($is_swap === 1 ? [$s1_id, $s2_id] : [$s1_id]);
            if (!$schedules) return $this->reject('ไม่พบข้อมูลเวร', 404);
            $first = $schedules[(string)$s1_id];
            $second = $is_swap === 1 ? $schedules[(string)$s2_id] : null;
            if ((string)$first['user_id'] !== (string)$currentUserId || ($second && (string)$second['user_id'] !== (string)$user2_id))
                return $this->reject('ไม่มีสิทธิ์เปลี่ยนเวรนี้', 403);
            if ((int)$first['status'] !== 1 || ($second && (int)$second['status'] !== 1))
                return $this->reject('เวรนี้ไม่อยู่ในสถานะที่เปลี่ยนได้', 409);

            $active = $this->conn->prepare('SELECT id FROM user WHERE id = ? AND status = 10 AND is_deleted = 0');
            $active->execute([$user2_id]);
            if (!$active->fetchColumn()) return $this->reject('ไม่พบผู้รับเวรที่ใช้งานได้', 400);
            $eligible = $this->conn->prepare('SELECT id FROM ven_user WHERE user_id = ? AND ven_name_sub_id = ? LIMIT 1');
            $eligible->execute([$user2_id, $first['ven_name_sub_id']]);
            if (!$eligible->fetchColumn()) return $this->reject('ผู้รับเวรไม่มีสิทธิ์ในหน้าที่นี้', 400);
            if ($second) {
                $eligible->execute([$currentUserId, $second['ven_name_sub_id']]);
                if (!$eligible->fetchColumn()) return $this->reject('ผู้ขอสลับไม่มีสิทธิ์ในหน้าที่ปลายทาง', 400);
            }

            $pending = $this->conn->prepare('SELECT id FROM ven_change WHERE status = 0 AND (s1_id = ? OR s2_id = ? OR s1_id = ? OR s2_id = ?) LIMIT 1');
            $pending->execute([$s1_id, $s1_id, $s2_id, $s2_id]);
            if ($pending->fetchColumn()) return $this->reject('เวรนี้อยู่ระหว่างรออนุมัติ', 409);

            $changeNo = 'CH-' . date('Ym') . '-' . bin2hex(random_bytes(6));
            $insert = $this->conn->prepare('INSERT INTO ven_change (change_no, s1_id, user1_id, user2_id, is_swap, s2_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())');
            $insert->execute([$changeNo, $s1_id, $currentUserId, $user2_id, $is_swap, $second ? $s2_id : null]);
            $update = $this->conn->prepare('UPDATE ven_schedule SET user_id = ?, status = 2 WHERE id = ?');
            $update->execute([$user2_id, $s1_id]);
            if ($second) $update->execute([$currentUserId, $s2_id]);
            $this->conn->commit();
            return ['success' => true, 'change_no' => $changeNo, 'date1' => $first['ven_date'], 'date2' => $second['ven_date'] ?? null];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Transfer failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'ไม่สามารถบันทึกคำขอเปลี่ยนเวรได้', 'code' => 500];
        }
    }

    public function cancelTransfer($change_id, $currentUserId) {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare('SELECT * FROM ven_change WHERE id = ?');
            $stmt->execute([$change_id]);
            $changeReq = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$changeReq) return $this->reject('ไม่พบข้อมูลใบเปลี่ยนเวร', 404);
            if ((string)$changeReq['user1_id'] !== (string)$currentUserId) return $this->reject('ไม่มีสิทธิ์ยกเลิกคำขอนี้', 403);

            $swapped = (int)$changeReq['is_swap'] === 1;
            $schedules = $this->lockSchedules($swapped ? [$changeReq['s1_id'], $changeReq['s2_id']] : [$changeReq['s1_id']]);
            if (!$schedules) return $this->reject('ไม่พบข้อมูลเวรที่ต้องคืนค่า', 409);
            $lockRequest = $this->conn->prepare('SELECT * FROM ven_change WHERE id = ? FOR UPDATE');
            $lockRequest->execute([$change_id]);
            $locked = $lockRequest->fetch(PDO::FETCH_ASSOC);
            if (!$locked || (int)$locked['status'] !== 0 || $locked['s1_id'] != $changeReq['s1_id'] || $locked['s2_id'] != $changeReq['s2_id'])
                return $this->reject('ยกเลิกได้เฉพาะคำขอที่รออนุมัติ', 409);
            $first = $schedules[(string)$changeReq['s1_id']];
            $second = $swapped ? $schedules[(string)$changeReq['s2_id']] : null;
            if ((string)$first['user_id'] !== (string)$changeReq['user2_id'] || (int)$first['status'] !== 2 ||
                ($second && ((string)$second['user_id'] !== (string)$changeReq['user1_id'] || (int)$second['status'] !== 2)))
                return $this->reject('ตารางเวรเปลี่ยนไปแล้ว ไม่สามารถยกเลิกคำขอได้', 409);

            $update = $this->conn->prepare('UPDATE ven_schedule SET user_id = ?, status = 1 WHERE id = ?');
            $update->execute([$changeReq['user1_id'], $changeReq['s1_id']]);
            if ($second) $update->execute([$changeReq['user2_id'], $changeReq['s2_id']]);
            $delete = $this->conn->prepare('DELETE FROM ven_change WHERE id = ? AND status = 0');
            $delete->execute([$change_id]);
            $this->conn->commit();
            $changeReq['date1'] = $first['ven_date'];
            $changeReq['date2'] = $second['ven_date'] ?? null;
            return ['success' => true, 'data' => $changeReq];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Cancel transfer failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'ไม่สามารถยกเลิกคำขอได้', 'code' => 500];
        }
    }
}
