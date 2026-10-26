<?php

declare(strict_types=1);

namespace ReactParallel\Runtime;

use parallel\Channel;

use function pcntl_async_signals;
use function pcntl_signal;

use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

final class SignalListener
{
    /**
     * @param Channel<Signal> $channel
     *
     * @api
     */
    public static function listen(Channel $channel): void
    {
        pcntl_async_signals(true);

        $channel = $channel;
        pcntl_signal(SIGINT, static function () use ($channel): void {
            $channel->send(Signal::sigint());
        });
        pcntl_signal(SIGTERM, static function () use ($channel): void {
            $channel->send(Signal::sigterm());
        });
        pcntl_signal(SIGHUP, static function () use ($channel): void {
            $channel->send(Signal::sighup());
        });
        pcntl_signal(SIGQUIT, static function () use ($channel): void {
            $channel->send(Signal::sigquit());
        });
    }
}
