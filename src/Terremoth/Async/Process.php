<?php

namespace Terremoth\Async;

use Closure;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    private const MAX_INT = 2147483647;

    private array $processTemplate = [PHP_BINARY, __DIR__ . DIRECTORY_SEPARATOR . 'background_processor.php', '{key}',
        '{length}', '&'];
    private int $key;

    public function __construct()
    {
        $this->key = mt_rand(0, self::MAX_INT); // communication key
        $this->processTemplate[2] = $this->key;

        if (PHP_OS_FAMILY === 'Windows') {
            $this->processTemplate = ['start', '""', '/B', PHP_BINARY, __DIR__ . DIRECTORY_SEPARATOR .
                'background_processor.php', $this->key, '{length}'];
        }
    }

    /**
     * @throws Exception
     */
    public function send(Closure $asyncFunction): int
    {
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

        $key = array_search('{length}', $this->processTemplate);
        $this->processTemplate[$key] =  $serializedLength;
        $process = new SymfonyProcess($this->processTemplate);
        return $process->run();
    }
}
