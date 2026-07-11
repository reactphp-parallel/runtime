<?php

declare(strict_types=1);

use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;

use function PHPStan\Testing\assertType;

$runtime = Runtime::create(new EventLoopBridge());

assertType('static-Closure(): void', (static fn () => $runtime->run(static function (): void {
    /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
    sleep(1);
})));

assertType('static-Closure(): void', (static fn () => $runtime->run(static function (int $time): void {
    /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
    sleep($time);
}, [1])));

assertType('true', $runtime->run(static fn (): bool => true));

assertType('int<1, max>|true', $runtime->run(static fn (): bool|int => time() % 2 !== 0 ? true : time()));

assertType('int<1, max>|true', $runtime->run(static fn (int $mod): bool|int => time() % $mod !== 0 ? true : time(), [2]));

assertType('bool|int<1, max>', $runtime->run(static fn (int $mod, bool $yolo): bool|int => time() % $mod !== 0 ? $yolo : time(), [2, (time() % 13 !== 0)]));

assertType('bool|non-empty-string', $runtime->run(static fn (int $mod, bool $yolo, string $oloy): bool|string => time() % $mod !== 0 ? $yolo : $oloy, [2, (time() % 13 !== 0), bin2hex(random_bytes(13))]));
