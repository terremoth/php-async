<?php

namespace Terremoth\AsyncTest;

use PHPUnit\Framework\TestCase;
use Terremoth\Async\PhpFile;
use InvalidArgumentException;
use Exception;

/**
 * @covers \Terremoth\Async\PhpFile
 */
class PhpFileTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        error_reporting(E_ALL);
        $this->tmpDir = sys_get_temp_dir() . '/php_file_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*'));
        rmdir($this->tmpDir);
    }

    private function makeFile(string $name, string $content): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        chmod($path, 0644);
        return $path;
    }

    /**
     * @throws Exception
     */
    public function testConstructAcceptsValidPhpFile(): void
    {
        $file = $this->makeFile('ok.php', "<?php echo 'ok';");
        new PhpFile($file);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws Exception
     */
    public function testConstructThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhpFile($this->tmpDir . '/nao_existe.php');
    }

    /**
     * @throws InvalidArgumentException|Exception
     */
    public function testConstructThrowsWhenFileIsNotReadable(): void
    {
        $this->expectException(Exception::class);
        new PhpFile($this->tmpDir);
    }

    public function testConstructThrowsWhenFileIsNotPhp(): void
    {
        $file = $this->makeFile('texto.txt', "isso nao é php");

        $this->expectException(Exception::class);

        new PhpFile($file);
    }

    /**
     * @throws Exception
     */
    public function testRunDoesNotThrowWithArgs(): void
    {
        $file = $this->makeFile('run.php', "<?php exit(0);");
        $obj = new PhpFile($file, ['arg1', 'arg2']);
        $obj->run();
        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws Exception
     */
    public function testRunDoesNotThrow(): void
    {
        $file = $this->makeFile('run.php', "<?php exit(0);");
        $obj = new PhpFile($file);

        $obj->run();

        $this->expectNotToPerformAssertions();
    }
}
