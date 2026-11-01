<?php

namespace Test\Lucinda\Framework;

use Lucinda\UnitTest\Result;

/**
 * Verifies the adapter against real MVC and PHP sessions in isolated processes
 *
 * Each scenario uses private temporary storage so identifier rotation, headers,
 * and process-level PHP settings cannot interfere with other tests.
 */
final class SessionTest
{
    /** Checks that constructing the adapter neither starts nor configures PHP. */
    public function isStarted()
    {
        return $this->run(<<<'PHP'
$before = session_get_cookie_params();
$adapter = new \Lucinda\Framework\Session($mvc);
$checks['construction leaves the session inactive'] = !$adapter->isStarted();
$checks['construction preserves cookie settings'] = session_get_cookie_params() === $before;
$mvc->start();
$checks['observes MVC startup'] = $adapter->isStarted();
$mvc->commit();
$checks['observes MVC shutdown'] = !$adapter->isStarted();
PHP);
    }

    /** Checks absent keys and values written directly through MVC. */
    public function get()
    {
        return $this->run(<<<'PHP'
$mvc->start();
$adapter = new \Lucinda\Framework\Session($mvc);
$checks['absent key returns null'] = $adapter->get('absent') === null;
$checks['absent read creates no entry'] = $_SESSION === [];
foreach (['text' => 'value', 'false' => false, 'zero' => 0, 'empty' => '', 'null' => null, 'array' => ['id' => 7]] as $key => $value) {
    $mvc->set($key, $value);
    $checks['reads MVC value '.$key] = $adapter->get($key) === $value;
}
PHP);
    }

    /** Checks that adapter writes share the injected MVC session. */
    public function set()
    {
        return $this->run(<<<'PHP'
$mvc->start();
$adapter = new \Lucinda\Framework\Session($mvc);
$adapter->set('identity', ['id' => 7]);
$checks['MVC sees adapter writes'] = $mvc->get('identity') === ['id' => 7];
$adapter->set('identity', false);
$checks['overwrites preserve false'] = $mvc->get('identity') === false;
PHP);
    }

    /** Checks that rotation preserves data and removes the old backing file. */
    public function regenerateID()
    {
        return $this->run(<<<'PHP'
$mvc->start();
$adapter = new \Lucinda\Framework\Session($mvc);
$adapter->set('application', ['cart' => [1, 2]]);
$oldID = session_id();
$mvc->commit();
$checks['old storage exists before rotation'] = is_file($directory.'/sess_'.$oldID);
$mvc->start();
$checks['rotation succeeds'] = $adapter->regenerateID();
$newID = session_id();
$checks['identifier changes'] = $newID !== $oldID;
$checks['old storage is deleted'] = !is_file($directory.'/sess_'.$oldID);
$checks['data survives rotation'] = $adapter->get('application') === ['cart' => [1, 2]];
$mvc->commit();
$mvc->start();
$checks['new identifier retains persisted data'] = session_id() === $newID
    && $adapter->get('application') === ['cart' => [1, 2]];
PHP);
    }

    /** Checks that clearing empties all data without rotating or closing. */
    public function clear()
    {
        return $this->run(<<<'PHP'
$mvc->start();
$adapter = new \Lucinda\Framework\Session($mvc);
$mvc->set('application', 'cart');
$adapter->set('authentication', 'identity');
$id = session_id();
$adapter->clear();
$checks['all entries are removed'] = $_SESSION === [];
$checks['session remains active'] = $adapter->isStarted();
$checks['identifier remains unchanged'] = session_id() === $id;
$adapter->set('after_clear', 'usable');
$mvc->commit();
$mvc->start();
$checks['cleared session remains writable'] = $mvc->get('after_clear') === 'usable'
    && !$mvc->contains('application') && !$mvc->contains('authentication');
PHP);
    }

    /**
     * Executes a scenario with isolated PHP state and translates its assertions
     *
     * @param string $scenario PHP statements populating named boolean checks
     * @return Result[] Assertions returned by the child process
     * @throws \RuntimeException If the process or scenario fails
     */
    private function run(string $scenario): array
    {
        $directory = __DIR__;
        // Package checkouts use vendor/autoload.php; installed packages share
        // the enclosing application's vendor/autoload.php.
        while (true) {
            if (is_file($directory.'/vendor/autoload.php')) {
                $autoload = $directory.'/vendor/autoload.php';
                break;
            }
            if (basename($directory) === 'vendor' && is_file($directory.'/autoload.php')) {
                $autoload = $directory.'/autoload.php';
                break;
            }
            $parent = dirname($directory);
            if ($parent === $directory) {
                throw new \RuntimeException('Cannot locate Composer autoload.php');
            }
            $directory = $parent;
        }
        $script = 'require '.var_export($autoload, true).';'. <<<'PHP'
$directory = sys_get_temp_dir().'/lucinda-session-'.bin2hex(random_bytes(12));
if (!mkdir($directory, 0700)) { throw new RuntimeException('Cannot create session storage'); }
ini_set('session.save_handler', 'files');
ini_set('session.save_path', $directory);
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
ini_set('session.gc_probability', '0');
$mvc = new \Lucinda\STDOUT\Session();
$checks = [];
try {
PHP;
        $script .= $scenario;
        $script .= <<<'PHP'
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    foreach (glob($directory.'/sess_*') as $file) { unlink($file); }
    rmdir($directory);
}
echo json_encode($checks, JSON_THROW_ON_ERROR);
PHP;
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', '-r', $script],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException('Cannot start isolated session test');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || $errors !== '') {
            throw new \RuntimeException('Session test process failed: '.$errors);
        }
        $results = [];
        foreach (json_decode($output, true, flags: JSON_THROW_ON_ERROR) as $message => $passed) {
            $results[] = new Result($passed, $message);
        }
        return $results;
    }
}
