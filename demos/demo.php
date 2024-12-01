<?php

require_once 'vendor/autoload.php';

use Terremoth\Async\Process;


$process = new Process();
echo time() . ' :: Sending process. You should not wait any longer to see next message: ' . PHP_EOL;
$process->send(function () {

    sleep(5);
    if (1 == 1) {
        echo 2;
        file_put_contents('demo.txt', 'Hello, World!');
    }
});

echo time() . ' :: This is the next message' . PHP_EOL;
