# PHP Async Process
Process functions or files **async** and in **parallel** without needing AMP, ReactPHP, RxPHP, Spatie/Fork, Fibers, Pthreads, Parallel, Revolt, 
Pcntl or Swoole.  

Just raw PHP! It is magic!

<!--
[![codecov](https://codecov.io/gh/terremoth/php-async/graph/badge.svg?token=W37V5EDERQ)](https://codecov.io/gh/terremoth/php-async)
[![Test Coverage](https://api.codeclimate.com/v1/badges/c6420e5f6ab01e70eed7/test_coverage)](https://codeclimate.com/github/terremoth/php-async/test_coverage)
-->
[![Psalm type coverage](https://shepherd.dev/github/terremoth/php-async/coverage.svg)](https://shepherd.dev/github/terremoth/php-async)
[![Psalm level](https://shepherd.dev/github/terremoth/php-async/level.svg)](https://shepherd.dev/github/terremoth/php-async)
[![Test Run Status](https://github.com/terremoth/php-async/actions/workflows/workflow.yml/badge.svg?branch=main)](https://github.com/terremoth/php-async/actions/workflows/workflow.yml)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/478adadc949c43b090fb22417e832326)](https://app.codacy.com/gh/terremoth/php-async/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/c6420e5f6ab01e70eed7/maintainability)](https://codeclimate.com/github/terremoth/php-async/maintainability)
[![License](https://img.shields.io/github/license/terremoth/php-async.svg?logo=mit&color=41bb13)](https://github.com/terremoth/php-async/blob/main/LICENSE)
![Packagist Downloads](https://img.shields.io/packagist/dt/terremoth/php-async?color=41bb13)

### Target Audience
For those who, for some reason, cannot or don't want to use Swoole or Parallel

### Why?
- Native way + no need to change php.ini
- Easy: just composer install the lib and use it
- Fast to learn
- Fast to use: no need to compile, no need to download pecl extensions
- Operating system agnostic

### How?
It uses a combination of:
- serializable-clojure lib
- Symfony/Process lib
- and PHP's native Shmop extension (available in any platform)

First it serializes your closure with its code,  
Then it sends to another *background* process to execute, through [shmop](https://www.php.net/manual/en/ref.shmop.php)

#### Some Possible Use Cases
- You got some user data and want to do a heavy processing somewhere without blocking;
- You want to send an email in you own platform without blocking with some data you got before;
- You want to create tons of processes at the same time, not blocking the main process/thread;
- Something will be heavy processed and will took time but your user does not need to know that at the time and don't need/want to wait;

#### Warning
it does not works on MSYS or MINGW shells! However, It will work fine on both Windows (cmd and powershell) and Linux.

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
$age = 30;
$name = 'John Doe';
$fruits = ['orange', 'apple', 'grape'];

$process->send(function () use ($age, $name, $fruits) {
    /*
    // Anything you want to process here + you can use closure vars for sending data to the other process
    */
});

// Another way to use is if you want to just process a file Asynchronously, you can do this:
$args = ['--verbose', '-n', '123'];
$asyncFile = new PhpFile('existing-php-file.php', $args); // make sure to pass the correct file with its path
$asyncFile->run();

```

That's it!
