# PHP Async Process
Process functions or files asynchronously without needing AMP, ReactPHP, RxPHP, Fibers, Pthreads, Parallel, Revolt, 
Pcntl or Swoole.  

Just raw PHP! It is magic!

[![codecov](https://codecov.io/gh/terremoth/php-async/graph/badge.svg?token=W37V5EDERQ)](https://codecov.io/gh/terremoth/php-async)
[![Test Coverage](https://api.codeclimate.com/v1/badges/c6420e5f6ab01e70eed7/test_coverage)](https://codeclimate.com/github/terremoth/php-async/test_coverage)
[![Psalm type coverage](https://shepherd.dev/github/terremoth/php-async/coverage.svg)](https://shepherd.dev/github/terremoth/php-async)
[![Psalm level](https://shepherd.dev/github/terremoth/php-async/level.svg)](https://shepherd.dev/github/terremoth/php-async)
[![Test Run Status](https://github.com/terremoth/php-async/actions/workflows/workflow.yml/badge.svg?branch=main)](https://github.com/terremoth/php-async/actions/workflows/workflow.yml)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/478adadc949c43b090fb22417e832326)](https://app.codacy.com/gh/terremoth/php-async/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/c6420e5f6ab01e70eed7/maintainability)](https://codeclimate.com/github/terremoth/php-async/maintainability)
[![License](https://img.shields.io/github/license/terremoth/php-async.svg?logo=mit&color=41bb13)](https://github.com/terremoth/php-async/blob/main/LICENSE)
![Packagist Downloads](https://img.shields.io/packagist/dt/terremoth/php-async?color=41bb13)

It uses a combination of:
- serializable-clojure lib
- Symfony/Process lib
- and PHP's native Shmop extension

**Warning: it does not works on MSYS/MINGW terminals!**. It will work fine on both Windows (cmd and powershell) and Linux.

See [demos/demo.php](demos/demo.php) for examples.

## Installation

```sh
composer require terremoth/php-async
```

## Documentation

```php
<?php

require_once 'vendor/autoload.php';

use Terremoth\Async\PhpFile;
use Terremoth\Async\Process;

$process = new Process();
$process->send(function () {
    /*
    // anything you want to process here
    // Important note: do not use closure vars, like:
    // $process->send(function () use ($var1, $var2, ...)  { ... });
    // since the closure will be processed in another file.
    // Write everything you want without outside dependencies here
    // In a future version I create communications variables between both processes
    */
});

$args = ['--verbose', '-n', '123'];
$asyncFile = new PhpFile('existing-php-file.php', $args); // make sure to pass the correct file with its path
$asyncFile->run();

```

That's it!
