<?php

require_once 'vendor/autoload.php';

use Laravel\SerializableClosure\SerializableClosure;

if (!isset($argv[1])) {
    fwrite(STDERR, 'Error: Key not provided');
    exit(1);
}

$key = (int)$argv[1];

$shmopInstance = shmop_open($key, 'a', 0, 0);
$length = shmop_size($shmopInstance);
$data = shmop_read($shmopInstance, 0, $length);

/**
 * @var SerializableClosure $serializedClosure
 * @var callable $closure
 */
$serializedClosure = unserialize($data);
$closure = $serializedClosure->getClosure();
$closure();

exit(0);
