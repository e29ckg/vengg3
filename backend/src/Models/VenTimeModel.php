<?php

class VenTimeModel
{
    public function __construct(private PDO $db) {}

    public function list(): array
    {
        $rows = $this->db->query('SELECT id, name_th, time_period, srt FROM ven_time ORDER BY srt, id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['used_count'] = $this->usageCount($row);
        }
        return $rows;
    }

    public function save(array $data): int
    {
        $id = isset($data['id']) && $data['id'] !== '' ? self::positiveInteger($data['id'], 'รหัสช่วงเวลา') : null;
        $name = trim(is_string($data['name_th'] ?? null) ? $data['name_th'] : '');
        $period = trim(is_string($data['time_period'] ?? null) ? $data['time_period'] : '');
        $period = str_replace(':', '.', preg_replace('/\s+/', '', $period));
        $sort = self::positiveInteger($data['srt'] ?? null, 'ลำดับ');

        if (!preg_match('/\A[^()\r\n]{1,50}\z/u', $name) || $name === '') {
            throw new InvalidArgumentException('ชื่อช่วงเวลาต้องมี 1–50 ตัวอักษรและไม่มีวงเล็บ');
        }
        if (!preg_match('/\A(?:[01]\d|2[0-3])\.[0-5]\d-(?:[01]\d|2[0-3])\.[0-5]\d\z/', $period)) {
            throw new InvalidArgumentException('ช่วงเวลาต้องเป็นรูปแบบ 08.30-16.30');
        }
        [$start, $end] = explode('-', $period);
        if ($start === $end) {
            throw new InvalidArgumentException('เวลาเริ่มและสิ้นสุดต้องต่างกัน');
        }
        if ($sort > 9999) {
            throw new InvalidArgumentException('ลำดับต้องไม่เกิน 9999');
        }

        $existing = $id === null ? null : $this->find($id);
        if ($id !== null && $existing === null) {
            throw new OutOfBoundsException('ไม่พบช่วงเวลาเวร');
        }
        if ($existing !== null && $this->usageCount($existing) > 0 &&
            ($existing['name_th'] !== $name || $existing['time_period'] !== $period)) {
            throw new DomainException('ช่วงเวลานี้ถูกใช้ในชื่อเวรแล้ว เปลี่ยนได้เฉพาะลำดับ');
        }

        $duplicate = $this->db->prepare('SELECT id FROM ven_time WHERE name_th = ? AND time_period = ? AND id <> ? LIMIT 1');
        $duplicate->execute([$name, $period, $id ?? 0]);
        if ($duplicate->fetchColumn() !== false) {
            throw new DomainException('มีช่วงเวลาเวรนี้อยู่แล้ว');
        }

        if ($id === null) {
            $stmt = $this->db->prepare('INSERT INTO ven_time (name_th, time_period, srt) VALUES (?, ?, ?)');
            $stmt->execute([$name, $period, $sort]);
            return (int)$this->db->lastInsertId();
        }
        $stmt = $this->db->prepare('UPDATE ven_time SET name_th = ?, time_period = ?, srt = ? WHERE id = ?');
        $stmt->execute([$name, $period, $sort, $id]);
        return $id;
    }

    public function delete(mixed $value): void
    {
        $id = self::positiveInteger($value, 'รหัสช่วงเวลา');
        $row = $this->find($id);
        if ($row === null) {
            throw new OutOfBoundsException('ไม่พบช่วงเวลาเวร');
        }
        if ($this->usageCount($row) > 0) {
            throw new DomainException('ช่วงเวลานี้ถูกใช้ในชื่อเวรแล้ว จึงลบไม่ได้');
        }
        $stmt = $this->db->prepare('DELETE FROM ven_time WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name_th, time_period, srt FROM ven_time WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function usageCount(array $row): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ven_name WHERE dn = ?');
        $stmt->execute([$row['name_th'] . '(' . $row['time_period'] . ')']);
        return (int)$stmt->fetchColumn();
    }

    private static function positiveInteger(mixed $value, string $label): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($number === false) {
            throw new InvalidArgumentException($label . 'ไม่ถูกต้อง');
        }
        return $number;
    }
}
