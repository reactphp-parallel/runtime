#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Functional test: spawn 128 threads via one Runtime, receive SIGTERM on a worker,
 * forward to main, echo confirmation, close the Runtime, and exit cleanly.
 */

use parallel\Runtime as ParallelRuntime;
use React\EventLoop\Loop;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Runtime\Runtime;

require __DIR__ . '/../../vendor/autoload.php';

$threadCount = 128;

// Create the Runtime wrapper (1 thread with signal forwarding)
$runtime = Runtime::create(new EventLoopBridge());

// Create additional parallel runtimes for the 128 threads
$parallelRuntimes = [];
for ($i = 0; $i < $threadCount - 1; $i++) {
    $parallelRuntimes[] = new ParallelRuntime();
}

// Register SIGTERM handler in the main process
Loop::addSignal(SIGTERM, static function () use ($runtime, $parallelRuntimes, $threadCount): void {
    echo 'SIGTERM received, closing ' . $threadCount . ' threads' . "\n";
    $runtime->close();
    foreach ($parallelRuntimes as $parallelRuntime) {
        $parallelRuntime->close();
    }

    Loop::stop();
});

// Schedule sleep tasks on all parallel runtimes to keep them alive
foreach ($parallelRuntimes as $parallelRuntime) {
    $parallelRuntime->run(static function (): void {
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.sleep */
        sleep(60);
    });
}

echo 'Ready with ' . $threadCount . ' threads' . "\n";

// Run the event loop - it will stay alive until Loop::stop() is called by the signal handler
Loop::run();

echo 'Exited cleanly' . "\n";
