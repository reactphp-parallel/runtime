<?php

declare(strict_types=1);

namespace ReactParallel\Runtime;

use Closure;
use parallel\Channel;
use parallel\Runtime as ParallelRuntime;
use React\EventLoop\Loop;
use ReactParallel\EventLoop\EventLoopBridge;

use function file_get_contents;
use function file_put_contents;
use function posix_getpid;
use function posix_kill;
use function React\Async\async;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;

use const WyriHaximus\Constants\ComposerAutoloader\LOCATION;

final readonly class Runtime
{
    private ParallelRuntime $runtime;

    /** @var Channel<Signal> */
    private Channel $signalChannel;

    /** @api */
    public static function create(EventLoopBridge $eventLoopBridge): self
    {
        return new self($eventLoopBridge, LOCATION);
    }

    public function __construct(private EventLoopBridge $eventLoopBridge, string $autoload)
    {
        $channelName = '.well-known/react-parallel/runtime/signals/' . uniqid('', true);
        /** @phpstan-ignore assign.propertyType */
        $this->signalChannel = Channel::make($channelName, Channel::Infinite);
        $this->runtime       = new ParallelRuntime($this->createBootstrapFile($autoload, $channelName));
        $this->forwardSignals();
    }

    /**
     * @param (Closure():T)|(Closure(A1):T)|(Closure(A1,A2):T)|(Closure(A1,A2,A3):T)|(Closure(A1,A2,A3,A4):T)|(Closure(A1,A2,A3,A4,A5):T)|(Closure():void)|(Closure(A1):void)|(Closure(A1,A2):void)|(Closure(A1,A2,A3):void)|(Closure(A1,A2,A3,A4):void)|(Closure(A1,A2,A3,A4,A5):void) $callable
     * @param array{}|array{A1}|array{A1,A2}|array{A1,A2,A3}|array{A1,A2,A3,A4}|array{A1,A2,A3,A4,A5}                                                                                                                                                                                   $args
     *
     * @return (
     *      $callable is (Closure():T) ? T : (
     *          $callable is (Closure(A1):T) ? T : (
     *              $callable is (Closure(A1,A2):T) ? T : (
     *                  $callable is (Closure(A1,A2,A3):T) ? T : (
     *                      $callable is (Closure(A1,A2,A3,A4):T) ? T : (
     *                          $callable is (Closure(A1,A2,A3,A4,A5):T) ? T : null
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     *
     * @template T
     * @template A1 (any number of function arguments, see https://github.com/phpstan/phpstan/issues/8214)
     * @template A2
     * @template A3
     * @template A4
     * @template A5
     * @api
     */
    public function run(Closure $callable, array $args = []): mixed
    {
        return $this->eventLoopBridge->await(
            $this->runtime->run(
                $callable,
                $args,
            ),
        );
    }

    /** @api */
    public function close(): void
    {
        $this->runtime->close();
    }

    /** @api */
    public function kill(): void
    {
        $this->runtime->kill();
    }

    private function forwardSignals(): void
    {
        Loop::addTimer(0.0, async(function (): void {
            foreach ($this->eventLoopBridge->observe($this->signalChannel) as $signal) {
                /** @phpstan-ignore property.nonObject, argument.type */
                posix_kill(posix_getpid(), $signal->number);
            }
        }));
    }

    private function createBootstrapFile(string $autoload, string $channelName): string
    {
        $file = tempnam(sys_get_temp_dir(), 'react_parallel_');
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.filePutContents */
        file_put_contents(
            $file,
            str_replace(
                ['{{AUTOLOAD}}', '{{CHANNEL}}'],
                [$autoload, $channelName],
                /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents, argument.type */
                file_get_contents(__DIR__ . '/../etc/bootstrap/signals.php.template'),
            ),
        );

        return $file;
    }
}
