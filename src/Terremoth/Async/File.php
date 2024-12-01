<?php

namespace Terremoth\Async;

use Exception;
use Symfony\Component\Process\Process as SymfonyProcess;

class File
{
    /**
     * @throws Exception
     */
    public function __construct(private readonly string $file, private readonly array $args = [])
    {
        if (!is_readable($this->file)) {
            throw new Exception('File ' . $this->file . ' does not exists or is not readable!');
        }
    }

    public function run(): int
    {
        $template = [PHP_BINARY, $this->file, ...$this->args, '&'];

        if (PHP_OS_FAMILY === 'Windows') {
            $template = ['start', '""', '/B', PHP_BINARY, $this->file, ...$this->args];
        }

        $process = new SymfonyProcess($template);
        return $process->run();
    }
}
