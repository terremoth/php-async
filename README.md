# PHP Async Process
Process functions or files asynchronously without needing AMP, ReactPHP, RxPHP, Fibers, Pthreads, Parallel, Revolt, 
Pcntl or Swoole.  

Just raw PHP! It is magic!

[![CodeCov](https://codecov.io/gh/terremoth/vendor/graph/badge.svg?token=TOKEN)](https://app.codecov.io/gh/terremoth/vendor)
[![Psalm type coverage](https://shepherd.dev/github/terremoth/vendor/coverage.svg)](https://shepherd.dev/github/terremoth/vendor)
[![Psalm level](https://shepherd.dev/github/terremoth/vendor/level.svg)](https://shepherd.dev/github/terremoth/vendor)
[![Test Run Status](https://github.com/terremoth/vendor/actions/workflows/workflow.yml/badge.svg?branch=main)](https://github.com/terremoth/vendor/actions/workflows/workflow.yml)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/CODE)](https://app.codacy.com/gh/terremoth/vendor/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Code Climate Maintainability](https://api.codeclimate.com/v1/badges/CODE/maintainability)](https://codeclimate.com/github/terremoth/vendor/maintainability)
[![License](https://img.shields.io/github/license/terremoth/vendor.svg?logo=gnu&color=41bb13)](https://github.com/terremoth/vendor/blob/main/LICENSE)
![Packagist Downloads](https://img.shields.io/packagist/dt/terremoth/vendor?color=41bb13)

It uses a combination of:
- serializable-clojure lib
- Symfony/Process lib
- and PHP's native Shmop extension

See [demos/demo.php](demos/demo.php) for examples.

## Installation

```sh
composer require terremoth/php-async
```

## Documentation

```php
<?php

require_once 'vendor/autoload.php';

use Terremoth\Async\File;
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
$asyncFile = new File('existing-php-file.php', $args); // make sure to pass the correct file with its path
$asyncFile->run();

```

That's it!
