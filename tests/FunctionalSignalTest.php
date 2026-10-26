<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Runtime;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function fgets;
use function microtime;
use function posix_kill;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function str_contains;
use function stream_get_contents;

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

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertNotNull($process, 'Process should start');

        // Read stdout until we see "Ready"
        $output    = '';
        $startTime = microtime(true);
        $status    = null;
        while (microtime(true) - $startTime < 120) { // 2 minute timeout
            /** @phpstan-ignore argument.type */
            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }

            $line = fgets($pipes[1]);
            if ($line === false) {
                continue;
            }

            $output .= $line;
            if (str_contains($output, 'Ready with 128 threads')) {
                break;
            }
        }

        self::assertStringContainsString('Ready with 128 threads', $output, 'Process should report 128 threads ready');

        // Send SIGTERM to the process
        /** @phpstan-ignore offsetAccess.notFound */
        posix_kill($status['pid'], SIGTERM);

        // Wait for the process to exit and collect remaining output
        /** @phpstan-ignore argument.type */
        $exitCode = proc_close($process);
        $output  .= stream_get_contents($pipes[1]);

        self::assertSame(0, $exitCode, 'Process should exit cleanly');
        self::assertStringContainsString('SIGTERM received', $output, 'Output should confirm SIGTERM was received');
        self::assertStringContainsString('Exited cleanly', $output, 'Output should confirm clean exit');
    }
}
