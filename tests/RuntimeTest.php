<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use parallel\Runtime\Error\Closed;
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
    /** @test */
    public function convertSuccess(): void
    {
        $sleep   = 3;
        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $result = $runtime->run(static function (int $sleep): int {
                sleep($sleep);

                return $sleep;
            }, [$sleep]);
        } finally {
            $runtime->kill();
        }

        self::assertSame($sleep, $result);
    }

    /** @test */
    public function convertFailure(): void
    {
        self::expectException(LatchcombException::class);
        self::expectExceptionMessage('Rethrow exception');

        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $three = $runtime->run(static function (): void {
                sleep(3);

                throw new LatchcombException('Rethrow exception');
            });
        } finally {
            $runtime->close();
        }
    }

    /** @test */
    public function weClosedTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<int> $promise */
        $promise = timedPromise(1, $runtime)->then(static function (Runtime $runtime) {
            return $runtime->run(static function (): int {
                return 3;
            });
        });

        Loop::futureTick(static function () use ($runtime): void {
            $runtime->close();
        });

        await($promise);
    }

    /** @test */
    public function weKilledTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<int> $promise */
        $promise = timedPromise(1, $runtime)->then(static function (Runtime $runtime) {
            return $runtime->run(static function (): int {
                return 3;
            });
        });

        Loop::futureTick(static function () use ($runtime): void {
            $runtime->kill();
        });

        await($promise);
    }
}
