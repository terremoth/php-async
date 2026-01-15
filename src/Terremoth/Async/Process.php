<?php

namespace Terremoth\Async;

use Closure;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;

class Process
{
    private const MAX_INT = 2147483647;

    public function __construct(private int $shmopKey = 0)
    {
        if (!$this->shmopKey) {
            $this->shmopKey = mt_rand(0, self::MAX_INT); // communication key
        }
    }

    /**
     * @throws Exception
     */
    public function send(Closure $asyncFunction): void
    {
        $dirSlash = DIRECTORY_SEPARATOR;
        $serialized = serialize(new SerializableClosure($asyncFunction));
        $length = mb_strlen($serialized);

        set_error_handler(function (int $errno, string $error) {
            throw new Exception($error);
        });

        try {
            $shmopInstance = shmop_open($this->shmopKey, 'n', 0660, $length);

            if ($shmopInstance === false) {
                $existing = shmop_open($this->shmopKey, 'a', 0660, 0);

                if ($existing === false) {
                    throw new Exception(
                        'Could not access existing shmop instance with key: ' . $this->shmopKey
                    );
                }

                if (shmop_delete($existing) === false) {
                    throw new Exception(
                        'Could not delete existing shmop instance with key: ' . $this->shmopKey
                    );
                }

                $shmopInstance = shmop_open($this->shmopKey, 'c', 0660, $length);

                if ($shmopInstance === false) {
                    throw new Exception(
                        'Could not recreate shmop instance with key: ' . $this->shmopKey
                    );
                }
            }
        } finally {
            restore_error_handler();
        }

        $bytesWritten = shmop_write($shmopInstance, $serialized, 0);

        if ($bytesWritten < $length) {
            throw new Exception('Error: Could not write the entire data to shared memory with length: ' .
                $length . '. Bytes written: ' . $bytesWritten . PHP_EOL);
        }

        $fileWithPath = __DIR__ . $dirSlash . 'background_processor.php';
        $file = new PhpFile($fileWithPath, [(string)$this->shmopKey]);
        $file->run();
    }
}
