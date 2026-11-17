<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\UnitTest\Result;

/**
 * Owns a child runtime and its isolated atomic-backend test lifecycle
 *
 * APCu is enabled at child startup. Redis and Memcached test servers belong
 * only to that child; application cache endpoints are never consulted.
 */
final class AtomicTestSession
{
    /** Backend selected for this isolated test runtime */
    private string $backend;

    /** @var resource|null Child PHP process retained by this session */
    private mixed $process = null;

    /** @var array<int, resource> Child request and response pipes */
    private array $pipes = [];

    /**
     * Captures the backend without starting any service yet
     *
     * @param string $backend redis, valkey, memcached or apcu
     */
    public function __construct(string $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Starts the private test runtime on first use
     *
     * @throws \RuntimeException If the child process cannot be started
     */
    private function open(): void
    {
        if (is_resource($this->process)) {
            return;
        }
        $this->process = proc_open([
            PHP_BINARY,
            '-d',
            'apc.enable_cli=1',
            '-r',
            'require "vendor/autoload.php"; '
                . '\\Test\\Lucinda\\Framework\\Support\\AtomicTestSession::work($argv[1]);',
            $this->backend,
        ], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $this->pipes, dirname(__DIR__, 2));
        if (!is_resource($this->process)) {
            throw new \RuntimeException('Cannot start atomic test worker');
        }
        stream_set_timeout($this->pipes[1], 10);
        stream_set_blocking($this->pipes[2], false);
    }

    /**
     * Serves assertions inside the isolated child PHP runtime
     *
     * @param string $backend Backend selected by the parent test session
     */
    public static function work(string $backend): void
    {
        $fixture = null;
        while (($line = fgets(STDIN)) !== false) {
            try {
                $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if ($fixture === null) {
                    $fixture = new AtomicBackendFixture($backend);
                }
                $response = ['checks' => $fixture->run($request['operation'])];
            } catch (\Throwable $error) {
                $response = ['error' => $error->getMessage()];
            }
            echo json_encode($response, JSON_THROW_ON_ERROR) . "\n";
            fflush(STDOUT);
        }
    }

    /**
     * Runs one test case and converts child assertions to native test results
     *
     * @param string $operation Case corresponding to a public driver method
     * @return list<Result> Assertions understood by the library's runner
     */
    public function run(string $operation): array
    {
        try {
            $this->open();
            $request = json_encode(['operation' => $operation]);
            if (fwrite($this->pipes[0], $request . "\n") === false) {
                throw new \RuntimeException('Atomic test worker input failed');
            }
            fflush($this->pipes[0]);
            $line = fgets($this->pipes[1]);
            if ($line === false) {
                $error = stream_get_contents($this->pipes[2]);
                throw new \RuntimeException('Atomic test worker: ' . $error);
            }
            $response = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            if (isset($response['error'])) {
                throw new \RuntimeException($response['error']);
            }
            $results = [];
            foreach ($response['checks'] as [$condition, $message]) {
                $results[] = new Result($condition, $message);
            }
            return $results;
        } catch (\Throwable $error) {
            return [new Result(false, $error->getMessage())];
        }
    }

    /**
     * Closes the worker, which also terminates its private cache server
     */
    public function __destruct()
    {
        if (!is_resource($this->process)) {
            return;
        }
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        proc_close($this->process);
    }
}
