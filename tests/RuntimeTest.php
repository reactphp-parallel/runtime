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

use function extension_loaded;
use function pcntl_async_signals;
use function pcntl_signal;
use function posix_getpid;
use function posix_kill;
use function React\Async\await;
use function sleep;
use function WyriHaximus\React\timedPromise;

use const SIGTERM;

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
        self::expectExceptionMessageIsOrContains('Rethrow exception');

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
        self::expectExceptionMessageIsOrContains('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<3> $promise */
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
        self::expectExceptionMessageIsOrContains('Runtime closed');

        $runtime = Runtime::create(new EventLoopBridge());

        /** @var PromiseInterface<3> $promise */
        $promise = timedPromise(1, $runtime)->then(static fn (Runtime $runtime) => $runtime->run(static fn (): int => 3));

        Loop::futureTick(static function () use ($runtime): void {
            $runtime->kill();
        });

        await($promise);
    }

    #[Test]
    public function signalChannelIsConfigured(): void
    {
        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $result = $runtime->run(static fn (): bool => extension_loaded('pcntl'));

            self::assertTrue($result);
        } finally {
            $runtime->close();
        }
    }

    #[Test]
    public function signalsAreForwardedToMainProcess(): void
    {
        $signalReceived = false;

        pcntl_signal(SIGTERM, static function () use (&$signalReceived): void {
            $signalReceived = true;
        });
        pcntl_async_signals(true);

        $runtime = Runtime::create(new EventLoopBridge());

        try {
            $mainPid = posix_getpid();
            $mainPid = $mainPid;
            $runtime->run(static function (int $mainPid): void {
                posix_kill($mainPid, SIGTERM);
            }, [$mainPid]);
        } finally {
            $runtime->close();
        }

        self::assertTrue($signalReceived);
    }

    #[Test]
    public function signalsAreForwardedViaLoopAddSignal(): void
    {
        $signalReceived = false;

        $runtime = Runtime::create(new EventLoopBridge());

        try {
            Loop::addSignal(SIGTERM, static function () use (&$signalReceived): void {
                $signalReceived = true;
            });

            $mainPid = posix_getpid();
            $mainPid = $mainPid;
            $runtime->run(static function (int $mainPid): void {
                posix_kill($mainPid, SIGTERM);
            }, [$mainPid]);
        } finally {
            $runtime->close();
        }

        self::assertTrue($signalReceived);
    }

    #[Test]
    public function workerSignalListenerForwardsSignal(): void
    {
        $signalReceived = false;

        pcntl_signal(SIGTERM, static function () use (&$signalReceived): void {
            $signalReceived = true;
        });
        pcntl_async_signals(true);

        $runtime = Runtime::create(new EventLoopBridge());

        try {
            // The worker thread's SignalListener catches SIGTERM and forwards it via channel
            // The Runtime's forwardSignals() then sends it to the main process via posix_kill
            $runtime->run(static function (): void {
                // Signal ourselves (the worker thread) - SignalListener should catch it
                posix_kill(posix_getpid(), SIGTERM);
            });
        } finally {
            $runtime->close();
        }

        self::assertTrue($signalReceived);
    }
}
