<?php

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 1);
ini_set('error_log', 'php-async-errors-' . date('YmdH') . '.log');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'script_functions.php';

use Laravel\SerializableClosure\SerializableClosure;

if (!isset($argv[1])) {
    error('Shmop Key not provided');
    exit(1);
}

$key = (int)$argv[1];

$shmopInstance = shmop_open($key, 'a', 0, 0);

if (!$shmopInstance) {
    error('Could not open Shmop');
    exit(1);
}

$length = shmop_size($shmopInstance);

if ($length === 0) {
    error('Shmop length cannot be zero!');
    exit(1);
}

$dataCompressed = shmop_read($shmopInstance, 0, $length);
$data = stringFromMemoryBlock($dataCompressed);

/**
 * @var SerializableClosure $serializedClosure
 * @var callable $closure
 */
$serializedClosure = unserialize($data);
$closure = $serializedClosure->getClosure();
$closure();

exit(0);
