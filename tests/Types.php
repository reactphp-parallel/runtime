<?php

declare(strict_types=1);

use ReactParallel\Runtime\Runtime;
use ReactParallel\EventLoop\EventLoopBridge;

use function PHPStan\Testing\assertType;

$pool = Runtime::create(new EventLoopBridge());

assertType('bool', $pool->run(static function (): bool {
    return true;
}));

assertType('bool|int', $pool->run(static function (): bool|int {
    return time() % 2 !== 0 ? true : time();
}));

assertType('null', $pool->run(static function () {
}));
