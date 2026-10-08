<?php
require_once __DIR__ . '/../Services/ProgramUpdateService.php';

final class ProgramUpdateController
{
    private string $root;
    public function __construct()
    {
        $this->root = dirname(__DIR__, 3);
    }

    public function status(): void
    {
        try {
            echo json_encode((new ProgramUpdateService($this->root))->status());
        } catch (RuntimeException $error) {
            http_response_code(503);
            echo json_encode(['error' => $error->getMessage()]);
        }
    }

    public function update(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $expected = $input['latest'] ?? null;
        if (!is_string($expected) || !preg_match('/\A[a-f0-9]{40}\z/', $expected)) {
            http_response_code(400);
            echo json_encode(['error' => 'ตรวจเวอร์ชันก่อนเริ่มอัปเดต']);
            return;
        }
        if (PHP_OS_FAMILY !== 'Windows' || !function_exists('proc_open')) {
            http_response_code(400);
            echo json_encode(['error' => 'อัปเดตอัตโนมัติรองรับ XAMPP บน Windows ที่ติดตั้งจาก Git เท่านั้น']);
            return;
        }
        $lock = fopen(sys_get_temp_dir() . '/vengg3-installer.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            http_response_code(409);
            echo json_encode(['error' => 'มีการติดตั้งหรืออัปเดตกำลังทำงานอยู่']);
            return;
        }
        set_time_limit(0);
        ignore_user_abort(true);
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no');
        while (ob_get_level() > 0) { ob_end_flush(); }
        $send = static function (array $event): void {
            echo json_encode($event, JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
            flush();
        };
        try {
            $process = @proc_open(['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File',
                $this->root . '/deploy/update-program.ps1', '-ProjectRoot', $this->root, '-ExpectedCommit', $expected],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $this->root, null, ['bypass_shell' => true]);
            if (!is_resource($process)) { throw new RuntimeException('เริ่มอัปเดตไม่ได้'); }
            fclose($pipes[0]);
            while (($line = fgets($pipes[1])) !== false) {
                $send(['type' => 'line', 'text' => trim($line)]);
            }
            fclose($pipes[1]);
            $send(['type' => 'done', 'ok' => proc_close($process) === 0]);
        } catch (Throwable $error) {
            $send(['type' => 'line', 'text' => 'เริ่มอัปเดตไม่ได้ กรุณาตรวจ Git, PowerShell และสิทธิ์ Apache']);
            $send(['type' => 'done', 'ok' => false]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
