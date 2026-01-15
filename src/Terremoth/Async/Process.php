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
            $this->shmopKey = mt_rand(0, self::MAX_INT);
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
            try {
                $shmopInstance = $this->openShmop($this->shmopKey, 'n', 0660, $length);
            } catch (Exception) {
                $shmopInstance = false;
            }

            if ($shmopInstance === false) {
                $existing = $this->openShmop($this->shmopKey, 'a', 0660, 0);

                if ($existing === false) {
                    throw new Exception(
                        'Could not access existing shmop instance with key: ' . $this->shmopKey
                    );
                }

                if ($this->deleteShmop($existing) === false) {
                    throw new Exception(
                        'Could not delete existing shmop instance with key: ' . $this->shmopKey
                    );
                }

                $shmopInstance = $this->openShmop($this->shmopKey, 'c', 0660, $length);

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

        if ($bytesWritten === false) {
            throw new Exception(
                'shmop_write failed when writing to shared memory with key: ' . $this->shmopKey
            );
        }

        if ($bytesWritten !== $length) {
            throw new Exception(
                'Could not write all bytes to shared memory. Expected: '
                . $length . ', Written: ' . $bytesWritten
            );
        }

        $file = new PhpFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'background_processor.php',
            [(string) $this->shmopKey]
        );

        $file->run();
    }

    protected function writeToShmop(Shmop $shmopInstance, string $data, int $offset): int|false
    {
        return shmop_write($shmopInstance, $data, $offset);
    }

    protected function openShmop(int $key, string $mode, int $permissions, int $size): Shmop|false
    {
        return shmop_open($key, $mode, $permissions, $size);
    }

    protected function deleteShmop(Shmop $shmopInstance): bool
    {
        return shmop_delete($shmopInstance);
    }
}
