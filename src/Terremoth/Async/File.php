<?php

namespace Terremoth\Async;

use Exception;
use Symfony\Component\Process\Process as SymfonyProcess;

readonly class File
{
    /**
     * @throws Exception
     */
    public function __construct(private string $file, private array $args = [])
    {
        if (!is_readable($this->file)) {
            throw new Exception('File ' . $this->file . ' does not exists or is not readable!');
        }
    }

    public function run(): void
    {
        $template = [PHP_BINARY, $this->file, ...$this->args, '&'];

        if (PHP_OS_FAMILY === 'Windows') {
            $template = ['start', '""', '/B', PHP_BINARY, $this->file, ...$this->args];
        }

        $process = new SymfonyProcess($template);
        $process->start();
    }
}
