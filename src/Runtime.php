<?php declare(strict_types=1);

namespace ReactParallel\Runtime;

use Closure;
use parallel\Future;
use parallel\Runtime as ParallelRuntime;
use React\Promise\PromiseInterface;
use ReactParallel\FutureToPromiseConverter\FutureToPromiseConverter;
use function React\Promise\resolve;

final class Runtime
{
    private ParallelRuntime $runtime;

    private FutureToPromiseConverter $futureToPromiseConverter;

    public function __construct(FutureToPromiseConverter $futureToPromiseConverter, string $autoload)
    {
        $this->futureToPromiseConverter = $futureToPromiseConverter;
        $this->runtime                  = new ParallelRuntime($autoload);
    }

    /**
     * @param  mixed[] $args
     */
    public function run(Closure $callable, array $args = []): PromiseInterface
    {
        $future = $this->runtime->run($callable, $args);

        if ($future instanceof Future) {
            return $this->futureToPromiseConverter->convert($future);
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
