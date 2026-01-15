<?php

namespace Terremoth\AsyncTest;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use Shmop;
use Terremoth\Async\Process;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionProperty;

/**
 * @covers \Terremoth\Async\Process
 * @covers \Terremoth\Async\PhpFile
 */
class ProcessTest extends TestCase
{
    protected function setUp(): void
    {
        error_reporting(E_ALL);
        if (!extension_loaded('shmop')) {
            $this->markTestSkipped('Shmop extension not available');
        }
    }

    public function testConstructorGeneratesRandomKeyWhenZero(): void
    {
        $process = new Process(0);
        $ref = new ReflectionProperty(Process::class, 'shmopKey');
        $key = intval($ref->getValue($process));
        $this->assertGreaterThan(0, $key);
    }

    public function testConstructorKeepsProvidedKey(): void
    {
        $process = new Process(12345);
        $reflection = new ReflectionProperty(Process::class, 'shmopKey');
        $this->assertSame(12345, $reflection->getValue($process));
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function testSendActuallyWritesSerializedClosureToShmop(): void
    {
        $key = ftok(__FILE__, 'a') ?: random_int(1, 1000000);
        $process = new Process($key);

        $closure = function (): int {
            return 42;
        };

        $process->send($closure);

        $shmop = shmop_open($key, 'a', 0, 0);
        $this->assertNotFalse($shmop);

        $size = shmop_size($shmop);
        $this->assertGreaterThan(0, $size);

        $raw = shmop_read($shmop, 0, $size);
        $data = rtrim($raw, "\0");
        $unserialized = unserialize($data);
        $this->assertInstanceOf(SerializableClosure::class, $unserialized);

        //shmop_delete($shmop);
    }

    /**
     * @throws RandomException|Exception
     */
    public function testSendThrowsWhenNotAllBytesAreWritten(): void
    {
        $key = ftok(__FILE__, 'b') ?: random_int(1, 1000000);

        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$key])
            ->onlyMethods(['writeToShmop'])
            ->getMock();

        $process
            ->expects($this->once())
            ->method('writeToShmop')
            ->willReturn(10);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not write all bytes');

        $process->send(fn() => true);
    }

    public function testSendThrowsExceptionWhenWriteFailsCompletely(): void
    {
        $key = 123456;
        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$key])
            ->onlyMethods(['writeToShmop'])
            ->getMock();

        $process->expects($this->once())
            ->method('writeToShmop')
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('shmop_write failed');

        $process->send(fn() => true);
    }

    /**
     * @throws Exception
     */
    public function testSendHandlesShmopCollisionAndRecreation(): void
    {
        $key = 987654;

        $process = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([$key])
            ->onlyMethods(['openShmop', 'writeToShmop'])
            ->getMock();

        $process->expects($this->exactly(3))
            ->method('openShmop')
            ->willReturnCallback(function (int $k, string $mode) {
                unset($k);
                if ($mode === 'n') {
                    throw new Exception('shmop already exists');
                }

                $tempKey = random_int(1000000, 9999999);
                $dummy = shmop_open($tempKey, 'c', 0660, 1);

                if ($mode === 'c') {
                    $this->assertTrue(shmop_delete($dummy));
                }

                return $dummy;
            });


        $process->expects($this->once())
            ->method('writeToShmop')
            ->willReturnCallback(function (Shmop $shmop, string $data) {
                unset($shmop);
                return mb_strlen($data);
            });

        $process->send(fn() => 'test');
    }

    public function testSendThrowsExceptionWhenExistingShmopCannotBeAccessed(): void
    {
        $process = $this->getMockBuilder(Process::class)
            ->onlyMethods(['openShmop'])
            ->getMock();

        $process->expects($this->exactly(2))
            ->method('openShmop')
            ->willReturnCallback(function (int $key, string $mode) {
                unset($key);
                if ($mode === 'n') {
                    throw new Exception('Simulated creation failure');
                }
                if ($mode === 'a') {
                    return false;
                }
                return false;
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not access existing shmop instance');

        $process->send(fn() => true);
    }

    public function testSendThrowsExceptionWhenExistingShmopCannotBeDeleted(): void
    {
        $process = $this->getMockBuilder(Process::class)
            ->onlyMethods(['openShmop', 'deleteShmop'])
            ->getMock();

        $process->expects($this->exactly(2))
            ->method('openShmop')
            ->willReturnCallback(function (int $k, string $mode) {
                unset($k);
                if ($mode === 'n') {
                    throw new Exception('Simulated creation failure');
                }

                $tempKey = random_int(1000000, 9999999);
                $dummy = shmop_open($tempKey, 'c', 0660, 1);

                // Fix: Usa o retorno para satisfazer o Psalm e limpa a memória
                $this->assertTrue(shmop_delete($dummy));

                return $dummy;
            });

        $process->expects($this->once())
            ->method('deleteShmop')
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not delete existing shmop instance');

        $process->send(fn() => true);
    }

    public function testSendThrowsExceptionWhenShmopCannotBeRecreated(): void
    {
        $process = $this->getMockBuilder(Process::class)
            ->onlyMethods(['openShmop', 'deleteShmop'])
            ->getMock();

        $process->expects($this->exactly(3))
            ->method('openShmop')
            ->willReturnCallback(function (int $k, string $mode) {
                unset($k);
                if ($mode === 'n') {
                    throw new Exception('Simulated creation failure');
                }
                if ($mode === 'c') {
                    return false;
                }

                $tempKey = random_int(1000000, 9999999);
                $dummy = shmop_open($tempKey, 'c', 0660, 1);

                // Fix: Usa o retorno para satisfazer o Psalm e limpa a memória
                $this->assertTrue(shmop_delete($dummy));

                return $dummy;
            });

        $process->expects($this->once())
            ->method('deleteShmop')
            ->willReturn(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not recreate shmop instance');

        $process->send(fn() => true);
    }

    public function testErrorHandlerCapturesWarnings(): void
    {
        $process = $this->getMockBuilder(Process::class)
            ->onlyMethods(['openShmop'])
            ->getMock();

        $process->expects($this->exactly(2))
            ->method('openShmop')
            ->willReturnCallback(function (int $key, string $mode) {
                unset($key);
                if ($mode === 'n') {
                    throw new Exception('First failure');
                }
                if ($mode === 'a') {
                    trigger_error('Native shmop warning', E_USER_WARNING);
                    return false;
                }
                return false;
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Native shmop warning');

        $process->send(fn() => true);
    }
}
