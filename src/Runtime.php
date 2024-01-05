<?php

declare(strict_types=1);

namespace ReactParallel\Runtime;

use Closure;
use parallel\Future;
use parallel\Runtime as ParallelRuntime;
use ReactParallel\EventLoop\EventLoopBridge;

use const WyriHaximus\Constants\ComposerAutoloader\LOCATION;

final class Runtime
{
    private ParallelRuntime $runtime;

    public static function create(EventLoopBridge $eventLoopBridge): self
    {
        return new self($eventLoopBridge, LOCATION);
    }

    public function __construct(private EventLoopBridge $eventLoopBridge, string $autoload)
    {
        $this->runtime = new ParallelRuntime($autoload);
    }

    /**
     * @param (Closure():?T)    $callable
     * @param  array<int, mixed> $args
     *
     * @return ?T
     *
     * @template T
     */
    public function run(Closure $callable, array $args = []): mixed
    {
        $future = $this->runtime->run($callable, $args);

        if ($future instanceof Future) {
            return $this->eventLoopBridge->await($future);
        }

        return $future;
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
