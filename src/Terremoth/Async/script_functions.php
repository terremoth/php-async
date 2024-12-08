<?php

function error(string $error): void
{
    $error = 'Error: ' . $error;
    fwrite(STDERR, $error);
    error_log($error);
}

function stringFromMemoryBlock(string $value): string
{
    $position = strpos($value, "\0");
    if ($position === false) {
        return $value;
    }
    return substr($value, 0, $position);
}
