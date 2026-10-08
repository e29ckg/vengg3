<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Services/DocumentTemplateService.php';

final class DocumentTemplateServiceTest extends TestCase
{
    private string $directory;
    private DocumentTemplateService $service;
    private string $source;
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/vengg-template-test-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
        $this->source = __DIR__ . '/../resources/templates/duty_report_form.docx';
        $this->service = new DocumentTemplateService($this->directory, __DIR__ . '/../resources/templates');
    }
    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') as $file) unlink($file);
        rmdir($this->directory);
    }
    public function testPerDutyOverridesAndFallbacksStaySeparate(): void
    {
        self::assertSame($this->source, $this->service->resolve('duty', 1));
        $this->service->store('duty', 0, $this->source, 'default.docx');
        self::assertSame($this->directory . '/duty-default.docx', $this->service->resolve('duty', 2));
        $this->service->store('duty', 1, $this->source, 'specific.docx');
        self::assertSame($this->directory . '/duty-1.docx', $this->service->resolve('duty', 1));
        self::assertFalse($this->service->metadata('duty', 2)['custom']);
        self::assertSame('global', $this->service->metadata('duty', 2)['source']);
        $this->service->reset('duty', 1);
        self::assertSame($this->directory . '/duty-default.docx', $this->service->resolve('duty', 1));
    }
    public function testInvalidUploadDoesNotReplaceCurrentFile(): void
    {
        $this->service->store('shift', 0, $this->source, 'valid.docx');
        $path = $this->service->resolve('shift');
        $hash = hash_file('sha256', $path);
        $invalid = $this->directory . '/invalid.docx';
        file_put_contents($invalid, '<?php echo "not a document";');
        try { $this->service->store('shift', 0, $invalid, 'invalid.docx'); self::fail('Invalid ZIP accepted'); }
        catch (RuntimeException $error) { self::assertSame($hash, hash_file('sha256', $path)); }
    }
    public function testMacrosAreRejected(): void
    {
        $path = $this->directory . '/macro.docx';
        copy($this->source, $path);
        $zip = new ZipArchive();
        $zip->open($path);
        $zip->addFromString('word/vbaProject.bin', 'macro');
        $zip->close();
        $this->expectException(RuntimeException::class);
        $this->service->validate($path, 'macro.docx');
    }
    public function testInvalidScopeCannotChooseArbitraryPaths(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->resolve('../config/database.local.php', 0);
    }
}
