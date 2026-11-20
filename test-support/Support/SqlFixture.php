<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\SQL\Connection;
use Lucinda\SQL\DataSource;
use Lucinda\SQL\StatementResults;

/**
 * Owns a private MySQL process and schema for this test runtime.
 *
 * Unix sockets and a random temporary directory isolate it from configured
 * application databases. Shutdown terminates only the retained child process.
 */
final class SqlFixture
{
    private static ?self $instance = null;
    private string $directory;
    /** @var resource|null Foreground mysqld process */
    private mixed $server = null;

    private function __construct()
    {
        $this->directory = sys_get_temp_dir() . '/framework-sql-test-' . bin2hex(random_bytes(12));
        if (!mkdir($this->directory, 0700) || !mkdir($this->directory . '/data', 0700)) {
            throw new \RuntimeException('Cannot create private SQL fixture');
        }
        try {
            $process = proc_open([
                '/usr/sbin/mysqld', '--no-defaults', '--initialize-insecure',
                '--datadir=' . $this->directory . '/data',
                '--log-error=' . $this->directory . '/initialize.log',
            ], [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', $this->directory . '/initialize.out', 'a'],
                2 => ['file', $this->directory . '/initialize.out', 'a'],
            ], $pipes);
            if (!is_resource($process) || proc_close($process) !== 0) {
                throw new \RuntimeException('Private MySQL initialization failed');
            }
            $this->server = proc_open([
                '/usr/sbin/mysqld', '--no-defaults',
                '--datadir=' . $this->directory . '/data',
                '--socket=' . $this->directory . '/mysql.sock',
                '--pid-file=' . $this->directory . '/mysql.pid',
                '--log-error=' . $this->directory . '/server.log',
                '--skip-networking', '--mysqlx=0', '--innodb-buffer-pool-size=32M',
            ], [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ], $pipes);
            if (!is_resource($this->server)) {
                throw new \RuntimeException('Cannot start private MySQL');
            }
            $deadline = microtime(true) + 20;
            do {
                try {
                    $pdo = $this->pdo(false);
                    $pdo->exec('CREATE DATABASE framework_tests');
                    return;
                } catch (\PDOException) {
                    if (!proc_get_status($this->server)['running']) {
                        throw new \RuntimeException('Private MySQL stopped during startup');
                    }
                    usleep(50000);
                }
            } while (microtime(true) < $deadline);
            throw new \RuntimeException('Private MySQL startup timed out');
        } catch (\Throwable $error) {
            $this->close();
            throw $error;
        }
    }

    /** Retains one isolated server across test cases in this PHP process. */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** Opens an independent native connection to the private schema. */
    public function pdo(bool $withSchema = true): \PDO
    {
        return new \PDO(
            'mysql:unix_socket=' . $this->directory . '/mysql.sock'
                . ($withSchema ? ';dbname=framework_tests' : ''),
            'root',
            '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES => false]
        );
    }

    /** Injects a real fixture PDO without requiring socket configuration in the library API. */
    public function connection(): Connection
    {
        $connection = new Connection();
        (new \ReflectionProperty(Connection::class, 'pdo'))->setValue($connection, $this->pdo());
        (new \ReflectionProperty(Connection::class, 'dataSource'))->setValue(
            $connection,
            new DataSource(new \SimpleXMLElement(
                '<server driver="mysql" host="localhost" username="root" schema="framework_tests"/>'
            ))
        );
        return $connection;
    }

    /** Executes static fixture SQL and retains the native connection in its result. */
    public static function results(string $sql): StatementResults
    {
        $pdo = self::instance()->pdo();
        return new StatementResults($pdo, $pdo->query($sql));
    }

    /** Stops the owned process before removing its private files. */
    private function close(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
            $this->server = null;
        }
        $root = realpath($this->directory);
        $prefix = realpath(sys_get_temp_dir()) . '/framework-sql-test-';
        if ($root === false || !str_starts_with($root, $prefix)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir() && !$file->isLink()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($root);
    }

    public function __destruct()
    {
        $this->close();
    }
}
