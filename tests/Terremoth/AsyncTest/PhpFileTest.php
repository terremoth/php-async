<?php

namespace Terremoth\AsyncTest;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Process\Process as SymfonyProcess;
use Terremoth\Async\PhpFile;
use InvalidArgumentException;
use Exception;

/**
 * @covers \Terremoth\Async\PhpFile
 */
class PhpFileTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        error_reporting(E_ALL);
        $this->tmpDir = sys_get_temp_dir() . '/php_file_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') as $file) {
            unlink($file);
        }
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


    public function testRunExecutesWindowsFlowWhenOsIsWindows(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_win') . '.php';
        file_put_contents($tempFile, '<?php echo "hello";');

        try {
            $phpFile = $this->getMockBuilder(PhpFile::class)
                ->setConstructorArgs([$tempFile, ['arg1', 'arg2']])
                ->onlyMethods(['getOsFamily', 'startProcess', 'executeCommand'])
                ->getMock();

            $phpFile->expects($this->once())
                ->method('getOsFamily')
                ->willReturn('Windows');

            $phpFile->expects($this->once())
                ->method('startProcess')
                ->willReturnCallback(function (SymfonyProcess $process) use ($tempFile) {
                    $commandLine = $process->getCommandLine();

                    $this->assertStringContainsString('start', $commandLine);
                    $this->assertStringContainsString('/B', $commandLine);
                    $this->assertStringContainsString($tempFile, $commandLine);
                    $this->assertStringContainsString('arg1', $commandLine);
                });

            $phpFile->expects($this->never())
                ->method('executeCommand');

            $phpFile->run();
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testRunExecutesUnixFlowWhenOsIsLinux(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_unix') . '.php';
        file_put_contents($tempFile, '<?php echo "hello";');

        try {
            $phpFile = $this->getMockBuilder(PhpFile::class)
                ->setConstructorArgs([$tempFile, ['argA']])
                ->onlyMethods(['getOsFamily', 'startProcess', 'executeCommand'])
                ->getMock();

            $phpFile->expects($this->once())
                ->method('getOsFamily')
                ->willReturn('Linux');

            $phpFile->expects($this->never())
                ->method('startProcess');

            $phpFile->expects($this->once())
                ->method('executeCommand')
                ->willReturnCallback(function (string $command) use ($tempFile) {
                    $this->assertStringContainsString('> /dev/null 2>&1 &', $command);
                    $this->assertStringContainsString($tempFile, $command);
                    $this->assertStringContainsString('argA', $command);
                });

            $phpFile->run();
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function testStartProcessDelegatesToSymfonyProcess(): void
    {
        $tempFile = $this->makeFile('dummy_process.php', '<?php');
        $phpFile = new PhpFile($tempFile);

        $symfonyProcessMock = $this->createMock(SymfonyProcess::class);

        $symfonyProcessMock->expects($this->once())
            ->method('start');

        $reflectionMethod = new ReflectionMethod(PhpFile::class, 'startProcess');
        $reflectionMethod->invoke($phpFile, $symfonyProcessMock);
    }
}
