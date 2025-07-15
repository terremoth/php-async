<?php

require_once 'vendor/autoload.php';

use Terremoth\Async\Process;
use Terremoth\Async\PhpFile;

$process = new Process();

echo date('c') . ' :: Sending process. You should not wait any longer to see next message: ' . PHP_EOL;

try {
    $age = 30;
    $name = 'John Doe';
    $fruits = ['orange', 'apple', 'grape'];
    $process->send(function () use ($age, $name, $fruits) {
        sleep(5);
        echo 123; // you should not see this anywhere
        file_put_contents(
            'demo.txt',
            "Age: $age\nName: $name\nFruits: " . implode(', ', $fruits) . ' - ' . date('c')
        );
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
