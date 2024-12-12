<?php

namespace Tests\Terremoth\AsyncTest;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Terremoth\Async\PhpFile;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

class PhpFileTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    #[CoversNothing]
    public function shouldOnlyAcceptPhpFiles(): void
    {
        $this->expectException(Exception::class);
        $tempFile = tmpfile();
        $htmlString = '<!DOCTYPE html><html lang="en"><body><h1>Hello World</h1></body></html>';
        fwrite($tempFile, $htmlString);
        $path = stream_get_meta_data($tempFile)['uri'];
        $phpFile = new PhpFile($path);
        $phpFile->run();
        fclose($tempFile);
        unlink($path);
    }

    /**
     * @throws InvalidArgumentException|Exception
     */
    #[Test]
    #[CoversNothing]
    public function shouldOnlyAcceptReadablePhpFiles(): void
    {
        $this->expectException(InvalidArgumentException::class);

        if (PHP_OS_FAMILY == 'Windows') {
            $this->expectException(Exception::class);
        }

        $tempFile = tmpfile();
        $htmlString = '<!DOCTYPE html><html lang="en"><body><h1>Hello World</h1></body></html>';
        fwrite($tempFile, $htmlString);
        $path = stream_get_meta_data($tempFile)['uri'];
        $writeAndExecOnly = 0330;
        chmod($path, $writeAndExecOnly); // set as only write and exec, not readable
        $phpFile = new PhpFile($path);
        $phpFile->run();
        fclose($tempFile);
        unlink($path);
    }
}
