<?php

declare(strict_types=1);

use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;

use function PHPStan\Testing\assertType;

$runtime = Runtime::create(new EventLoopBridge());

assertType('Closure(): void', (static fn () => $runtime->run(static function (): void {
    sleep(1);
})));

assertType('Closure(): void', (static fn () => $runtime->run(static function (int $time): void {
    sleep($time);
}, [1])));

assertType('true', $runtime->run(static function (): bool {
    return true;
}));

assertType('int<1, max>|true', $runtime->run(static function (): bool|int {
    return time() % 2 !== 0 ? true : time();
}));

assertType('int<1, max>|true', $runtime->run(static function (int $mod): bool|int {
    return time() % $mod !== 0 ? true : time();
}, [2]));

assertType('bool|int<1, max>', $runtime->run(static function (int $mod, bool $yolo): bool|int {
    return time() % $mod !== 0 ? $yolo : time();
}, [2, (time() % 13 !== 0)]));

assertType('bool|non-empty-string', $runtime->run(static function (int $mod, bool $yolo, string $oloy): bool|string {
    return time() % $mod !== 0 ? $yolo : $oloy;
}, [2, (time() % 13 !== 0), bin2hex(random_bytes(13))]));
