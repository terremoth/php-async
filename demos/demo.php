<?php

require_once 'vendor/autoload.php';

use Terremoth\Async\Process;
use Terremoth\Async\PhpFile;

$process = new Process();

echo date('c') . ' :: Sending process. You should not wait any longer to see next message: ' . PHP_EOL;

try {
    $process->send(function () {
        sleep(5);
        echo 123;
        file_put_contents('demo.txt', 'Hello, World! At: ' . date('c'));
    });
} catch (Exception $e) {
    echo $e->getMessage();
}

echo date('c') . ' :: This is the next message' . PHP_EOL;
echo date('c') . ' :: Now let\'s process a file that takes a long time...' . PHP_EOL;

try {
    $file = new PhpFile(__DIR__ . DIRECTORY_SEPARATOR . 'time-wasting-file.php');
    $file->run();
    echo date('c') . ' :: Ended...' . PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}
