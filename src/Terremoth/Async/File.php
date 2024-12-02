<?php

namespace Terremoth\Async;

use Exception;
use Symfony\Component\Process\Process as SymfonyProcess;

readonly class File
{
    /**
     * @param string $file
     * @throws Exception
     * @param array<array-key, null> $args
     */
    public function __construct(private string $file, private array $args = [])
    {
        if (!is_readable($this->file)) {
            throw new Exception('File ' . $this->file . ' does not exists or is not readable!');
        }
    }

    public function run(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $template = ['start', '""', '/B', PHP_BINARY, $this->file, ...$this->args];
            $process = new SymfonyProcess($template);
            $process->start();
            return;
        }

        $args = implode(' ', $this->args);
        exec(PHP_BINARY . ' ' . $this->file . ' ' . $args . '  > /dev/null 2>&1 &');
    }
}
