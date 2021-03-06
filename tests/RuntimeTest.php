<?php declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use React\EventLoop\Factory;
use React\Promise\ExtendedPromiseInterface;
use ReactParallel\EventLoop\EventLoopBridge;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use ReactParallel\Runtime\Runtime;
use parallel\Runtime\Error\Closed;
use function Safe\sleep;
use function WyriHaximus\React\timedPromise;

/**
 * @internal
 */
final class RuntimeTest extends AsyncTestCase
{
    /**
     * @test
     */
    public function convertSuccess(): void
    {
        $loop = Factory::create();
        $runtime = Runtime::create(new EventLoopBridge($loop));

        /** @var ExtendedPromiseInterface $promise */
        $promise = $runtime->run(function (): int {
            sleep(3);

            return 3;
        });

        $promise->always(function () use ($runtime): void {
            $runtime->kill();
        });

        $three = $this->await($promise, $loop, 3.3);

        self::assertSame(3, $three);
    }

    /**
     * @test
     */
    public function convertFailure(): void
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage('Rethrow exception');

        $loop = Factory::create();
        $runtime = Runtime::create(new EventLoopBridge($loop));

        /** @var ExtendedPromiseInterface $promise */
        $promise = $runtime->run(function (): void {
            sleep(3);

            throw new \Exception('Rethrow exception');
        });

        $promise->always(function () use ($runtime): void {
            $runtime->close();
        });

        $three = $this->await($promise, $loop, 3.3);

        self::assertSame(3, $three);
    }

    /**
     * @test
     */
    public function weClosedTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $loop = Factory::create();
        $runtime = Runtime::create(new EventLoopBridge($loop));

        /** @var ExtendedPromiseInterface $promise */
        $promise = timedPromise($loop, 1, $runtime)->then(function (Runtime $runtime) {
            return $runtime->run(function (): int {
                return 3;
            });
        });

        $loop->futureTick(function () use ($runtime): void {
            $runtime->close();
        });

        try {
            $this->await($promise, $loop, 3.3);
        } catch (\Exception $exception) {
            throw $exception->getPrevious();
        }
    }

    /**
     * @test
     */
    public function weKilledTheThread(): void
    {
        self::expectException(Closed::class);
        self::expectExceptionMessage('Runtime closed');

        $loop = Factory::create();
        $runtime = Runtime::create(new EventLoopBridge($loop));

        /** @var ExtendedPromiseInterface $promise */
        $promise = timedPromise($loop, 1, $runtime)->then(function (Runtime $runtime) {
            return $runtime->run(function (): int {
                return 3;
            });
        });

        $loop->futureTick(function () use ($runtime): void {
            $runtime->kill();
        });

        try {
            $this->await($promise, $loop, 3.3);
        } catch (\Exception $exception) {
            throw $exception->getPrevious();
        }
    }
}
