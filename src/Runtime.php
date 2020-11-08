<?php

declare(strict_types=1);

namespace ReactParallel\Runtime;

use Closure;
use parallel\Future;
use parallel\Runtime as ParallelRuntime;
use React\Promise\PromiseInterface;
use ReactParallel\EventLoop\EventLoopBridge;

use function React\Promise\resolve;

use const WyriHaximus\Constants\ComposerAutoloader\LOCATION;

final class Runtime
{
    private ParallelRuntime $runtime;

    private EventLoopBridge $eventLoopBridge;

    public static function create(EventLoopBridge $eventLoopBridge): self
    {
        return new self($eventLoopBridge, LOCATION);
    }

    public function __construct(EventLoopBridge $eventLoopBridge, string $autoload)
    {
        $this->eventLoopBridge = $eventLoopBridge;
        $this->runtime         = new ParallelRuntime($autoload);
    }

    /**
     * @param  array<int, mixed> $args
     */
    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        $future = $this->runtime->run($callable, $args); /** @phpstan-ignore-line */

        if ($future instanceof Future) {
            return $this->eventLoopBridge->await($future);
        }

        return resolve($future);
    }

    public function close(): void
    {
        $this->runtime->close();
    }

    public function kill(): void
    {
        $this->runtime->kill();
    }
}
