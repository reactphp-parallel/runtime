<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use parallel\Channel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Runtime\Signal;
use ReactParallel\Runtime\SignalListener;
use WyriHaximus\TestUtilities\TestCase;

use function pcntl_async_signals;
use function pcntl_signal_get_handler;
use function posix_getpid;
use function posix_kill;

use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

final class SignalListenerTest extends TestCase
{
    #[Test]
    public function listenRegistersSignalHandlers(): void
    {
        /** @var Channel<Signal> $channel */
        $channel = Channel::make('test-signals', Channel::Infinite);

        SignalListener::listen($channel);

        pcntl_async_signals(false);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertNotFalse(pcntl_signal_get_handler(SIGINT));
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertNotFalse(pcntl_signal_get_handler(SIGTERM));
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertNotFalse(pcntl_signal_get_handler(SIGHUP));
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertNotFalse(pcntl_signal_get_handler(SIGQUIT));
    }

    /** @return array<string, array{int, string}> */
    public static function signals(): iterable
    {
        return [
            'SIGINT'  => [SIGINT, 'SIGINT'],
            'SIGTERM' => [SIGTERM, 'SIGTERM'],
            'SIGHUP'  => [SIGHUP, 'SIGHUP'],
            'SIGQUIT' => [SIGQUIT, 'SIGQUIT'],
        ];
    }

    #[Test]
    #[DataProvider('signals')]
    public function listenSendsSignalToChannel(int $signalNumber, string $signalName): void
    {
        /** @var Channel<Signal> $channel */
        $channel = Channel::make('test-signals-' . $signalName, Channel::Infinite);

        SignalListener::listen($channel);

        pcntl_async_signals(true);

        posix_kill(posix_getpid(), $signalNumber);

        $signal = $channel->recv();
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(Signal::class, $signal);
        self::assertSame($signalNumber, $signal->number);
        self::assertSame($signalName, $signal->name);
    }
}
