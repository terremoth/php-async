<?php

for ($i = 1; $i <= 5; $i++) {
    echo $i . '...' . PHP_EOL;
    sleep(1);
}

file_put_contents('demo-file.txt', 'Hello users! At: ' . date('c'));
