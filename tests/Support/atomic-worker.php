<?php

$directory = dirname(__DIR__, 2);
while (!is_file($directory . '/vendor/autoload.php')) {
    $parent = dirname($directory);
    if ($parent === $directory) {
        throw new RuntimeException('Cannot find the test autoloader');
    }
    $directory = $parent;
}
require $directory . '/vendor/autoload.php';

$fixture = null;
while (($line = fgets(STDIN)) !== false) {
    try {
        $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if ($fixture === null) {
            $fixture = new Test\Lucinda\Framework\Support\AtomicBackendFixture(
                $argv[1]
            );
        }
        $response = ['checks' => $fixture->run($request['operation'])];
    } catch (Throwable $error) {
        $response = ['error' => $error->getMessage()];
    }
    echo json_encode($response, JSON_THROW_ON_ERROR) . "\n";
    fflush(STDOUT);
}
