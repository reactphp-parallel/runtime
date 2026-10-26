<?php

declare(strict_types=1);

namespace ReactParallel\Runtime;

use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

/** @api */
final readonly class Signal
{
    private function __construct(
        public int $number,
        public string $name,
    ) {
    }

    public static function sigint(): self
    {
        return new self(SIGINT, 'SIGINT');
    }

    public static function sigterm(): self
    {
        return new self(SIGTERM, 'SIGTERM');
    }

    public static function sighup(): self
    {
        return new self(SIGHUP, 'SIGHUP');
    }

    public static function sigquit(): self
    {
        return new self(SIGQUIT, 'SIGQUIT');
    }
}
