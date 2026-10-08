<?php
declare(strict_types=1);

final class ProgramUpdateService
{
    public const REPOSITORY = 'https://github.com/e29ckg/vengg3.git';
    private string $root;
    private $runner;

    public function __construct(string $root, ?callable $runner = null)
    {
        $this->root = $root;
        $this->runner = $runner;
    }

    private function git(array $arguments): string
    {
        $command = array_merge(['git.exe', '-C', $this->root], $arguments);
        if ($this->runner !== null) {
            return ($this->runner)($command);
        }
        if (!function_exists('proc_open')) {
            throw new RuntimeException('PHP ต้องเปิด proc_open เพื่อเรียก Git');
        }
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, $this->root, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('เรียก Git ไม่ได้ กรุณาติดตั้ง Git และเริ่ม Apache ใหม่');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException('ตรวจ Git ไม่สำเร็จ ตรวจการติดตั้ง Git การเชื่อมต่อ GitHub และสิทธิ์ของ Apache');
        }
        return trim($output);
    }

    public function status(): array
    {
        $root = $this->git(['rev-parse', '--show-toplevel']);
        if (strcasecmp(str_replace('\\', '/', $root), str_replace('\\', '/', $this->root)) !== 0) {
            throw new RuntimeException('โฟลเดอร์เว็บต้องเป็น root ของ Git checkout');
        }
        $branch = $this->git(['branch', '--show-current']);
        $current = $this->git(['rev-parse', 'HEAD']);
        $remote = $this->git(['ls-remote', '--exit-code', self::REPOSITORY, 'refs/heads/main']);
        if (!preg_match('/\A([a-f0-9]{40})\s+refs\/heads\/main\z/', $remote, $matches)) {
            throw new RuntimeException('ไม่ได้รับเวอร์ชัน main ที่ถูกต้องจาก GitHub');
        }
        $dirty = $this->git(['status', '--porcelain', '--untracked-files=no']) !== '';
        return ['current' => $current, 'latest' => $matches[1], 'branch' => $branch,
            'dirty' => $dirty, 'available' => $current !== $matches[1],
            'canUpdate' => $branch === 'main' && !$dirty && $current !== $matches[1],
            'repository' => self::REPOSITORY];
    }
}
