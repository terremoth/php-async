<?php

namespace Terremoth\Async;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Process\Process as SymfonyProcess;

readonly class PhpFile
{
    /**
     * @param string $file
     * @throws Exception
     * @param array<array-key, null>|list{int} $args
     */
    public function __construct(private string $file, private array $args = [])
    {
        if (!is_readable($this->file)) {
            throw new InvalidArgumentException('Error: file ' . $this->file
                . ' does not exists or is not readable!');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->file);
        finfo_close($finfo);

        if (!in_array($mimeType, ['text/x-php', 'application/x-php', 'application/php', 'application/x-httpd-php'])) {
            throw new Exception('Error: file ' . $this->file . ' is not a PHP file!');
        }
    }

    public function run(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $template = ['start', "", '/B', PHP_BINARY, $this->file, ...$this->args];
            $process = new SymfonyProcess($template);
            $process->start();
            return;
        }

        $args = implode(' ', $this->args);
        exec(PHP_BINARY . ' ' . $this->file . ' ' . $args . '  > /dev/null 2>&1 &');
    }
}
