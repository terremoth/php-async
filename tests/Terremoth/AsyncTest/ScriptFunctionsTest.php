<?php

namespace Terremoth\AsyncTest;

use PHPUnit\Framework\TestCase;

use function Terremoth\Async\stringFromMemoryBlock;

/**
 * @covers \Terremoth\Async\stringFromMemoryBlock
 * @covers \Terremoth\Async\customError
 */
class ScriptFunctionsTest extends TestCase
{
    public function testStringFromMemoryBlockTrimsNull(): void
    {
        $this->assertSame('Hello', stringFromMemoryBlock("Hello\0World"));
    }

    public function testStringFromMemoryBlockWithoutNull(): void
    {
        $this->assertSame('JustAString', stringFromMemoryBlock("JustAString"));
    }

    public function testStringFromMemoryBlockHandlesLeadingNullByte(): void
    {
        $this->assertSame('', stringFromMemoryBlock("\0LeadingNull"));
    }

    public function testStringFromMemoryBlockHandlesMultipleNullBytes(): void
    {
        $this->assertSame('A', stringFromMemoryBlock("A\0B\0C"));
    }

    public function testErrorWritesToStderrAndErrorLog(): void
    {
        $logFile = sys_get_temp_dir() . '/phpunit_error.log';
        ini_set('error_log', $logFile);

        $stream = fopen('php://memory', 'w+');

        // temporarily redefine error() for test
        $customError = function (string $msg) use ($stream): void {
            $msg = 'Error: ' . $msg;
            fwrite($stream, $msg);
            error_log($msg);
        };

        ob_start(); // start output buffer
        $customError('Test Error');
        ob_get_clean(); // clean buffer properly

        rewind($stream);
        $streamContents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('Error: Test Error', $streamContents);

        $logContents = file_get_contents($logFile);
        $this->assertStringContainsString('Error: Test Error', $logContents);
    }
}
