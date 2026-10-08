<?php
require_once __DIR__ . '/../Services/DocumentTemplateService.php';

final class DocumentTemplateController
{
    private DocumentTemplateService $service;
    public function __construct(private PDO $db)
    {
        $root = dirname(__DIR__, 2);
        $this->service = new DocumentTemplateService($root . '/storage/document-templates', $root . '/resources/templates');
    }
    private function scope(array $input): array
    {
        $kind = $input['kind'] ?? '';
        $id = filter_var($input['ven_name_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!is_string($kind) || !in_array($kind, ['shift', 'duty'], true) || $id === false || $id < 0 || ($kind === 'shift' && $id !== 0)) throw new RuntimeException('ประเภทเทมเพลดไม่ถูกต้อง');
        if ($id > 0) {
            $query = $this->db->prepare('SELECT id FROM ven_name WHERE id = ?');
            $query->execute([$id]);
            if (!$query->fetchColumn()) throw new RuntimeException('ไม่พบประเภทเวรที่เลือก');
        }
        return [$kind, $id];
    }
    public function handle(string $action): void
    {
        try {
            if ($action === 'list') {
                $rows = [$this->service->metadata('shift') + ['label' => 'ใบเปลี่ยนเวร'], $this->service->metadata('duty') + ['label' => 'รายงานเวรเริ่มต้น']];
                $duties = $this->db->query('SELECT id, name FROM ven_name WHERE status = 1 ORDER BY srt, id')->fetchAll(PDO::FETCH_ASSOC);
                foreach ($duties as $duty) $rows[] = $this->service->metadata('duty', (int)$duty['id']) + ['label' => $duty['name']];
                echo json_encode(['templates' => $rows]);
                return;
            }
            [$kind, $id] = $this->scope(in_array($action, ['upload','validate'], true) ? $_POST : $_GET);
            if ($action === 'download') {
                $path = $this->service->resolve($kind, $id);
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment; filename="' . $kind . '-template-' . $id . '.docx"');
                header('Cache-Control: no-store');
                if (ob_get_length()) ob_clean();
                readfile($path);
                return;
            }
            if (in_array($action, ['upload','validate'], true)) {
                $file = $_FILES['template'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('อัปโหลดไม่สำเร็จ ใช้ไฟล์ .docx ไม่เกิน 2 MB');
                if ($action === 'validate') $this->service->validate($file['tmp_name'], $file['name']);
                else $this->service->store($kind, $id, $file['tmp_name'], $file['name']);
            } elseif ($action === 'reset') {
                $this->service->reset($kind, $id);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $error) {
            http_response_code(500);
            echo json_encode(['error' => 'อ่านข้อมูลประเภทเวรไม่สำเร็จ']);
        } catch (RuntimeException $error) {
            http_response_code(400);
            echo json_encode(['error' => $error->getMessage()]);
        }
    }
}
