<?php

namespace Terremoth\Async;

use Closure;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    private const MAX_INT = 2147483647;

    public function __construct(private int $key = 0)
    {
        if (!$this->key) {
            $this->key = mt_rand(0, self::MAX_INT); // communication key
        }
    }

    /**
     * @throws Exception
     */
    public function send(Closure $asyncFunction): void
    {
        $separator = DIRECTORY_SEPARATOR;
        $serialized = serialize(new SerializableClosure($asyncFunction));
        $serializedLength = strlen($serialized);
        $shmopInstance = shmop_open($this->key, 'c', 0660, $serializedLength);

        if (!$shmopInstance) {
            throw new Exception('Could not create shmop instance with key: ' . $this->key);
        }

        $bytesWritten = shmop_write($shmopInstance, $serialized, 0);

        if ($bytesWritten != $serializedLength) {
            throw new Exception('Could not write the entire data to shared memory with length: ' .
                $serializedLength . '. Bytes written: ' . $bytesWritten);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $arg = ['start', '""', '/B', PHP_BINARY, __DIR__ . $separator . 'background_processor.php', $this->key];
            $process = new SymfonyProcess($arg);
            $process->start();
            return;
        }

        exec(PHP_BINARY . __DIR__ . $separator . 'background_processor.php ' . $this->key .
            ' > /dev/null 2>&1 &');
    }
}
