<?php

declare(strict_types=1);

use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;

require __DIR__ . '/../vendor/autoload.php';

$runtime = Runtime::create(new EventLoopBridge());

echo $runtime->run(static function (): int {
    sleep(3);

    return 3;
}), PHP_EOL;
