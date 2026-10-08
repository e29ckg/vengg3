<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Services/ProgramUpdateService.php';

final class ProgramUpdateServiceTest extends TestCase
{
    private function service(string $branch = 'main', string $dirty = '', string $remote = ''): ProgramUpdateService
    {
        $root = 'C:/xampp/htdocs/vengg3';
        return new ProgramUpdateService($root, static function (array $command) use ($root, $branch, $dirty, $remote): string {
            $arguments = array_slice($command, 3);
            if ($arguments === ['rev-parse', '--show-toplevel']) return $root;
            if ($arguments === ['branch', '--show-current']) return $branch;
            if ($arguments === ['rev-parse', 'HEAD']) return str_repeat('a', 40);
            if ($arguments === ['status', '--porcelain', '--untracked-files=no']) return $dirty;
            if ($arguments === ['ls-remote', '--exit-code', ProgramUpdateService::REPOSITORY, 'refs/heads/main']) return $remote ?: str_repeat('b', 40) . "\trefs/heads/main";
            throw new RuntimeException('Unexpected Git command');
        });
    }

    public function testCleanMainCanUpdateFromFixedRepository(): void
    {
        $status = $this->service()->status();
        self::assertTrue($status['canUpdate']);
        self::assertSame(ProgramUpdateService::REPOSITORY, $status['repository']);
    }
    public function testLocalEditsDisableUpdate(): void
    {
        self::assertFalse($this->service('main', ' M backend/public/index.php')->status()['canUpdate']);
    }
    public function testOtherBranchCannotUpdate(): void
    {
        self::assertFalse($this->service('feature')->status()['canUpdate']);
    }
    public function testSameCommitIsAlreadyCurrent(): void
    {
        $status = $this->service('main', '', str_repeat('a', 40) . "\trefs/heads/main")->status();
        self::assertFalse($status['available']);
        self::assertFalse($status['canUpdate']);
    }
    public function testInvalidRemoteResponseIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service('main', '', 'untrusted response')->status();
    }
}
