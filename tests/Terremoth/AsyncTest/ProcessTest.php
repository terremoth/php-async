<?php

namespace Terremoth\AsyncTest;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
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
            return (3 * 2 + 34 | 2 ^ 1) - 1; // this is whatever, just for phpmd stops crying, the result is 42
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

        $unserialized->getClosure();

        // shmop_delete($shmop);
        // shmop_close($shmop); // deprecated
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

        $process->send(function () {
        });
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function testSendDoesNotThrowOnSuccess(): void
    {
        $key = ftok(__FILE__, 'c') ?: random_int(1, 1000000);

        $process = new Process($key);

        $process->send(function (): int {
            return 42;
        });

        $shmop = shmop_open($key, 'a', 0, 0);
        $this->assertNotFalse($shmop, 'Shared memory should exist after send');

        $size = shmop_size($shmop);
        $this->assertGreaterThan(0, $size, 'Shared memory should contain data');
    }
}
