<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function fclose;
use function is_resource;
use function microtime;
use function posix_kill;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function str_contains;
use function stream_get_contents;
use function stream_select;
use function stream_set_blocking;

use const SIGTERM;

final class FunctionalSignalTest extends TestCase
{
    #[Group('functional')]
    #[Test]
    public function oneHundredTwentyEightThreadsForwardSignalToMainProcess(): void
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            'php ' . __DIR__ . '/Functional/signal_forwarding_128_threads.php',
            $descriptorSpec,
            $pipes,
        );

        if (! is_resource($process)) {
            self::fail('Process should start');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output    = '';
        $errors    = '';
        $startTime = microtime(true);
        $status    = proc_get_status($process);
        while (microtime(true) - $startTime < 120) { // 2 minute timeout
            if (! $status['running']) {
                break;
            }

            $read   = [$pipes[1], $pipes[2]];
            $write  = [];
            $except = [];
            stream_select($read, $write, $except, 0, 100_000);

            $output .= (string) stream_get_contents($pipes[1]);
            $errors .= (string) stream_get_contents($pipes[2]);
            if (str_contains($output, 'Ready with 128 threads')) {
                break;
            }

            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }
        }

        $ready = str_contains($output, 'Ready with 128 threads');
        if ($ready && $status['running']) {
            posix_kill($status['pid'], SIGTERM);
        } elseif ($status['running']) {
            proc_terminate($process, 9);
        }

        $startTime = microtime(true);
        while ($ready && $status['running'] && microtime(true) - $startTime < 120) {
            $read   = [$pipes[1], $pipes[2]];
            $write  = [];
            $except = [];
            stream_select($read, $write, $except, 0, 100_000);

            $output .= (string) stream_get_contents($pipes[1]);
            $errors .= (string) stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
        }

        if ($status['running']) {
            proc_terminate($process, 9);
        }

        $output .= (string) stream_get_contents($pipes[1]);
        $errors .= (string) stream_get_contents($pipes[2]);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fclose */
        fclose($pipes[0]);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fclose */
        fclose($pipes[1]);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fclose */
        fclose($pipes[2]);
        $closedExitCode = proc_close($process);
        $exitCode       = $status['exitcode'] === -1 ? $closedExitCode : $status['exitcode'];

        self::assertStringContainsString('Ready with 128 threads', $output, 'Process should report 128 threads ready. ' . $errors);
        self::assertSame(0, $exitCode, 'Process should exit cleanly');
        self::assertStringContainsString('SIGTERM received', $output, 'Output should confirm SIGTERM was received');
        self::assertStringContainsString('Exited cleanly', $output, 'Output should confirm clean exit');
    }
}
