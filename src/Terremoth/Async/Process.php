<?php

namespace Terremoth\Async;

use Closure;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use Shmop;

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
        $serialized = serialize(new SerializableClosure($asyncFunction));
        $length = mb_strlen($serialized);

        set_error_handler(function (int $errorNumber, string $error) {
            throw new Exception($error . ' - ' . $errorNumber);
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

        $bytesWritten = $this->writeToShmop($shmopInstance, $serialized, 0);

        if ((bool)$bytesWritten === false) {
            throw new Exception(
                'shmop_write failed when writing to shared memory with key: ' . $this->shmopKey
            );
        }

        if ($bytesWritten !== $length) {
            throw new Exception(
                'Could not write all bytes to shared memory. Expected: '
                . $length . ', Written: ' . (int)$bytesWritten
            );
        }

        $file = new PhpFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'background_processor.php',
            [(string) $this->shmopKey]
        );

        $file->run();
    }

    /**
     * @param Shmop $shmopInstance
     * @param string $data
     * @param int $offset
     * @return int|false
     */
    protected function writeToShmop(Shmop $shmopInstance, string $data, int $offset): int|false
    {
        return shmop_write($shmopInstance, $data, $offset);
    }
}
