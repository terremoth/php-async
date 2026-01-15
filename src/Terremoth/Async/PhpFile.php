<?php

namespace Terremoth\Async;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Process\Process as SymfonyProcess;

class PhpFile
{
    /**
     * @param string $file
     * @param list<string> $args
     * @throws Exception
     */
    public function __construct(private string $file, private array $args = [])
    {
        if (!is_readable($this->file)) {
            throw new InvalidArgumentException('Error: file ' . $this->file
                . ' does not exists or is not readable!');
        }

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $this->file);
        finfo_close($fileInfo);

        if (!in_array($mimeType, ['text/x-php', 'application/x-php', 'application/php', 'application/x-httpd-php'])) {
            throw new Exception('Error: file ' . $this->file . ' is not a PHP file!');
        }
    }

    public function run(): void
    {
        if ($this->getOsFamily() === 'Windows') {
            $template = ['start', "", '/B', PHP_BINARY, $this->file, ...$this->args];
            $process = new SymfonyProcess($template);

            $this->startProcess($process);
            return;
        }

        $arguments = implode(' ', $this->args);
        $command = PHP_BINARY . ' ' . $this->file . ' ' . $arguments . ' > /dev/null 2>&1 &';

        $this->executeCommand($command);
    }

    protected function getOsFamily(): string
    {
        return PHP_OS_FAMILY;
    }

    protected function startProcess(SymfonyProcess $symfonyProcess): void
    {
        $symfonyProcess->start();
    }

    protected function executeCommand(string $command): void
    {
        exec($command);
    }
}
