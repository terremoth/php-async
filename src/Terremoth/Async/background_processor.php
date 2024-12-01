<?php

require_once 'vendor/autoload.php';

use Laravel\SerializableClosure\SerializableClosure;

if (!isset($argv[1])) {
    fwrite(STDERR, 'Error: Key not provided');
    exit(1);
}

if (!isset($argv[2])) {
    fwrite(STDERR, 'Error: length not provided');
    exit(1);
}

$key = (int)$argv[1];
$length = (int)$argv[2];

if ($length === 0) {
    fwrite(STDERR, 'Error: length cannot be zero');
    exit(1);
}

$shmopInstance = shmop_open($key, 'c', 400, $length);
$data = shmop_read($shmopInstance, 0, $length);

/**
 * @var SerializableClosure $serializedClosure
 * @var callable $closure
 */
$serializedClosure = unserialize($data);
$closure = $serializedClosure->getClosure();
$closure();

exit(0);
