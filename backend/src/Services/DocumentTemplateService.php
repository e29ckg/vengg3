<?php
declare(strict_types=1);

final class DocumentTemplateService
{
    public const MAX_BYTES = 2097152;
    public function __construct(private string $storage, private string $defaults) {}

    private function filename(string $kind, int $venId): string
    {
        if (!in_array($kind, ['shift', 'duty'], true) || $venId < 0 || ($kind === 'shift' && $venId !== 0)) {
            throw new RuntimeException('ประเภทเทมเพลดไม่ถูกต้อง');
        }
        return $kind === 'shift' ? 'shift-change.docx' : ($venId === 0 ? 'duty-default.docx' : "duty-$venId.docx");
    }

    private function choose(string $kind, int $venId = 0): string
    {
        $custom = $this->storage . '/' . $this->filename($kind, $venId);
        if (is_file($custom)) return $custom;
        if ($kind === 'duty' && $venId > 0 && is_file($this->storage . '/duty-default.docx')) return $this->storage . '/duty-default.docx';
        $default = $this->defaults . '/' . ($kind === 'shift' ? 'shift_change_form.docx' : 'duty_report_form.docx');
        if (!is_file($default)) throw new RuntimeException('ไม่พบไฟล์เทมเพลดเริ่มต้น');
        return $default;
    }
    public function resolve(string $kind, int $venId = 0): string {
        $path = $this->choose($kind, $venId);
        $this->validate($path, basename($path));
        return $path;
    }

    public function metadata(string $kind, int $venId = 0): array
    {
        $custom = $this->storage . '/' . $this->filename($kind, $venId);
        $path = $this->choose($kind, $venId);
        $valid = true; $error = null;
        try { $this->validate($path, basename($path)); }
        catch (RuntimeException $exception) { $valid = false; $error = $exception->getMessage(); }
        return ['kind' => $kind, 'ven_name_id' => $venId, 'custom' => is_file($custom),
            'source' => is_file($custom) ? 'custom' : (($kind === 'duty' && $venId > 0 && is_file($this->storage . '/duty-default.docx')) ? 'global' : 'bundled'),
            'size' => filesize($path), 'updated_at' => is_file($custom) ? gmdate('c', filemtime($custom)) : null, 'valid' => $valid, 'validation_error' => $error];
    }

    public function validate(string $path, string $originalName): void
    {
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'docx' || !is_file($path) || filesize($path) > self::MAX_BYTES || filesize($path) === 0) {
            throw new RuntimeException('ใช้ไฟล์ .docx ขนาดไม่เกิน 2 MB');
        }
        $zip = new ZipArchive();
        if (@$zip->open($path) !== true) throw new RuntimeException('ไฟล์นี้ไม่ใช่เอกสาร Word .docx ที่สมบูรณ์');
        try {
            $total = 0;
            $decodedTotal = 0;
            if ($zip->numFiles > 2000) throw new RuntimeException('เอกสารมีส่วนประกอบมากเกินไป');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $total += $entry['size'];
                if ($total > 20971520 || stripos($entry['name'], 'vbaProject') !== false || stripos($entry['name'], 'word/activeX/') === 0 || stripos($entry['name'], 'word/embeddings/') === 0) throw new RuntimeException('ไม่รองรับ macro, ActiveX, embedded objects หรือไฟล์ขนาดหลังแตกเกิน 20 MB');
                if (!str_ends_with($entry['name'], '/')) {
                    $stream = $zip->getStream($entry['name']);
                    if (!$stream) throw new RuntimeException('อ่านส่วนประกอบใน DOCX ไม่สำเร็จ');
                    $read = 0; $tail = '';
                    $xml = preg_match('/\.(?:xml|rels)$/i', $entry['name']) === 1;
                    try {
                        while (!feof($stream)) {
                            $chunk = @fread($stream, 8192);
                            if ($chunk === false || ($chunk === '' && !feof($stream))) throw new RuntimeException('DOCX มีข้อมูลเสียหาย');
                            $read += strlen($chunk); $decodedTotal += strlen($chunk);
                            if ($read > $entry['size'] || $decodedTotal > 20971520) throw new RuntimeException('ขนาดข้อมูลจริงใน DOCX เกินขอบเขต');
                            if ($xml && preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $tail . $chunk)) throw new RuntimeException('ไม่รองรับ DTD หรือ entity declarations ใน DOCX');
                            $tail = substr($chunk, -32);
                        }
                        if ($read !== $entry['size']) throw new RuntimeException('ขนาดส่วนประกอบ DOCX ไม่ตรงกับข้อมูลจริง');
                    } finally { fclose($stream); }
                }
                if (str_ends_with(strtolower($entry['name']), '.rels') && preg_match('/TargetMode\s*=\s*["\x27]External["\x27]/i', $zip->getFromIndex($i) ?: '')) throw new RuntimeException('เทมเพลดต้องฝังรูปและข้อมูลในไฟล์ ไม่เชื่อมไฟล์ภายนอก');
            }
            $types = $zip->getFromName('[Content_Types].xml');
            if ($zip->locateName('word/document.xml') === false || !is_string($types) ||
                !str_contains($types, 'wordprocessingml.document.main+xml') || stripos($types, 'macroEnabled') !== false || stripos($types, 'vbaProject') !== false) {
                throw new RuntimeException('ไฟล์ต้องเป็น Word .docx ที่ไม่มี macro');
            }
        } finally { $zip->close(); }
    }

    public function store(string $kind, int $venId, string $path, string $originalName): void
    {
        $name = $this->filename($kind, $venId);
        $this->validate($path, $originalName);
        if (!is_dir($this->storage) && !@mkdir($this->storage, 0750, true) && !is_dir($this->storage)) throw new RuntimeException('สร้างโฟลเดอร์เทมเพลดไม่ได้');
        $temporary = @tempnam($this->storage, 'template-');
        if (!$temporary) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่ได้');
        try {
            if (!@copy($path, $temporary) || !@rename($temporary, $this->storage . '/' . $name)) throw new RuntimeException('บันทึกเทมเพลดไม่ได้ กรุณาตรวจสิทธิ์โฟลเดอร์');
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    public function reset(string $kind, int $venId): void
    {
        $path = $this->storage . '/' . $this->filename($kind, $venId);
        if (is_file($path) && !@unlink($path)) throw new RuntimeException('คืนค่าเทมเพลดไม่สำเร็จ');
    }
}
