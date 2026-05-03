<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use parallel\Runtime\Error\Closed;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;
use TheOrville\Exceptions\LatchcombException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function React\Async\await;
use function sleep;
use function WyriHaximus\React\timedPromise;

final class RuntimeTest extends AsyncTestCase
{
    #[Test]
    public function convertSuccess(): void
    {
        $sleep   = 3;
        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $result = $runtime->run(static function (int $sleep): int {
                /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
                sleep($sleep);

                return $sleep;
            }, [$sleep]);
        } finally {
            $runtime->kill();
        }

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertSame($sleep, $result);
    }

    #[Test]
    public function convertFailure(): void
    {
        self::expectException(LatchcombException::class);
        self::expectExceptionMessage('Rethrow exception');

        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $runtime->run(static function (): never {
                /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
                sleep(3);

                throw new LatchcombException('Rethrow exception');
            });
        } finally {
            $runtime->close();
        }
    }

    #[Test]
    public function weClosedTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<int> $promise */
        $promise = timedPromise(1, $runtime)->then(static fn (Runtime $runtime) => $runtime->run(static fn (): int => 3));

        Loop::futureTick(static function () use ($runtime): void {
            $runtime->close();
        });

        await($promise);
    }

    #[Test]
    public function weKilledTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<int> $promise */
        $promise = timedPromise(1, $runtime)->then(static fn (Runtime $runtime) => $runtime->run(static fn (): int => 3));

        Loop::futureTick(static function () use ($runtime): void {
            $runtime->kill();
        });

        await($promise);
    }
}
