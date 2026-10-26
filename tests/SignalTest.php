<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Runtime\Signal;
use WyriHaximus\TestUtilities\TestCase;

use const SIGHUP;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

final class SignalTest extends TestCase
{
    /** @return array<string, array{callable(): Signal, int, string}> */
    public static function signalFactoryMethods(): iterable
    {
        return [
            'sigint'  => [Signal::class . '::sigint', SIGINT, 'SIGINT'],
            'sigterm' => [Signal::class . '::sigterm', SIGTERM, 'SIGTERM'],
            'sighup'  => [Signal::class . '::sighup', SIGHUP, 'SIGHUP'],
            'sigquit' => [Signal::class . '::sigquit', SIGQUIT, 'SIGQUIT'],
        ];
    }

    #[Test]
    #[DataProvider('signalFactoryMethods')]
    public function createsSignalWithCorrectNumberAndName(callable $factory, int $expectedNumber, string $expectedName): void
    {
        /** @var Signal $signal */
        $signal = $factory();

        self::assertSame($expectedNumber, $signal->number);
        self::assertSame($expectedName, $signal->name);
    }
}
