# Lucinda Framework Engine

Lucinda Framework Engine contains the adapters and small runtime services that connect the Lucinda MVC, security, logging, headers, SQL, NoSQL, and OAuth2 packages in a complete application.

- [Request and security integration](#request-and-security-integration)
- [Session adapter](#session-adapter)
- [OAuth2 adapters](#oauth2-adapters)
- [SQL and NoSQL providers](#sql-and-nosql-providers)
- [HTTP cache validation](#http-cache-validation)
- [Error reporting](#error-reporting)
- [Utilities](#utilities)
- [Failure boundaries](#failure-boundaries)
- [Development](#development)
- [License](#license)

Its goals are to:

- normalize MVC requests for the security workflow;
- adapt OAuth2 client drivers to the provider-neutral security contracts;
- create SQL connections and NoSQL drivers lazily from environment-specific XML;
- connect HTTP cache validation and STDERR reporting to application implementations;
- provide small shared utilities without making the underlying packages depend on one another.

Install it with Composer:

```bash
composer require lucinda/framework-engine
```

It requires PHP 8.5 or newer and SimpleXML. Individual integrations may also require the PHP extension used by their selected SQL or NoSQL driver.

## Request and security integration

`RequestBinder` copies the resolved route, HTTP method, parameters, context path, client address, and optional bearer token from `Lucinda\STDOUT\Request` into `Lucinda\WebSecurity\Request`:

```php
use Lucinda\Framework\RequestBinder;
use Lucinda\STDOUT\Request;

$mvcRequest = new Request();
$securityRequest = (new RequestBinder($mvcRequest, $resolvedRoute))->getResult();
```

Only an `Authorization` header beginning with `Bearer `, matched case-insensitively, supplies an access token. The prefix is removed and surrounding token whitespace is trimmed. Other authorization schemes result in an empty token.

`LoggingRequestInformation` performs a similar adaptation for `Lucinda\Logging\RequestInformation`. It prefers the current web request and records its route, user agent, and client address. When no valid web request exists, it falls back to the current console route:

```php
use Lucinda\Framework\LoggingRequestInformation;

$requestInformation = (new LoggingRequestInformation())->getRequestInformation();
```

The client address comes directly from the MVC request. Applications deployed behind proxies must apply their trusted-proxy policy before relying on it for security decisions.

## Session adapter

`Lucinda\Framework\Session` implements `Lucinda\WebSecurity\Session` by delegating
to the existing MVC session. It does not configure or start a session:

```php
// $mvcSession is the configured MVC session supplied by the application.
if (!$mvcSession->isStarted() && !$mvcSession->start()) {
    throw new RuntimeException("Unable to start the session");
}
$security = new Lucinda\WebSecurity\Wrapper(
    xml: $xml,
    request: $securityRequest,
    session: new Lucinda\Framework\Session($mvcSession)
);
```

MVC owns cookie settings, save handlers and startup. Security owns authentication
state and idle expiry, and requests ID rotation through the adapter. Rotation
invalidates the old session ID while retaining data. Clearing empties all session
data without closing the session. Missing adapters and inactive sessions fail
explicitly when session persistence is configured. Other security consumers can
implement the same contract using their own session facilities.

## OAuth2 adapters

`OAuth2\ServiceBinder` converts OAuth2 client drivers into the services expected by Lucinda Web Security:

```php
use Lucinda\Framework\OAuth2\ServiceBinder;

$services = (new ServiceBinder($oauth2ClientDrivers))->getServices();

$security = new Lucinda\WebSecurity\Wrapper(
    xml: $xml,
    request: $securityRequest,
    oauth2Drivers: $services,
    oauth2State: $oauth2State,
    session: new Lucinda\Framework\Session($mvcSession)
);
```

Services are keyed by the provider segment of the standard client-driver class name. For example, `Lucinda\OAuth2\Vendor\Google\Driver` becomes `Google`. Registering two drivers that resolve to the same provider name throws `LogicException` instead of silently replacing the first service.

`ServiceConverter` delegates authorization endpoint creation, authorization-code exchange, and user-information retrieval to the client driver. `UserInfoConverter` exposes the resulting identifier, display name, and email through the security DAO contract. Validation of provider responses remains the responsibility of the OAuth2 client package.

## SQL and NoSQL providers

Both providers read the complete application XML document and select the requested environment. Configuration is optional: when the relevant section or environment is absent, the provider remains empty and lookup throws the package-specific lookup exception.

### SQL

```xml
<sql>
    <production>
        <server
            driver="mysql"
            host="database.internal"
            port="3306"
            username="application"
            password="secret"
            schema="application"
            charset="utf8mb4"
        />
    </production>
</sql>
```

```php
use Lucinda\Framework\SqlConnectionProvider;

$provider = new SqlConnectionProvider($xml, "production");
$connection = $provider->getConnection();
```

Connections are opened on first access, cached by name, and disconnected during provider destruction. A single server is addressed with the default empty name. Multiple servers require unique, non-empty `name` attributes:

```xml
<sql>
    <production>
        <server name="primary" driver="mysql" host="primary.internal" username="application" />
        <server name="replica" driver="mysql" host="replica.internal" username="application" />
    </production>
</sql>
```

```php
$primary = $provider->getConnection("primary");
$replica = $provider->getConnection("replica");
```

Duplicate names are rejected during provider construction.

### NoSQL

Supported drivers are `apcu`, `memcached`, and `redis`:

```xml
<nosql>
    <production>
        <server name="" driver="apcu" />
        <server name="shared" driver="redis" host="cache.internal" />
    </production>
</nosql>
```

```php
use Lucinda\Framework\NoSqlDriverProvider;

$provider = new NoSqlDriverProvider($xml, "production");
$localCache = $provider->getDriver();
$sharedCache = $provider->getDriver("shared");
```

Drivers are created on first access and cached. Server-backed drivers connect lazily and disconnect during provider destruction. APCu is process-local and does not use a server connection, so an explicitly empty name is valid when it represents the default local cache. Duplicate names are rejected.

## HTTP cache validation

Applications declare an `AbstractCacheable` implementation on their `headers` element:

```xml
<headers cacheable="App\Http\ArticleCacheable" />
```

The implementation calculates its Last-Modified timestamp and ETag from the request and response supplied by the engine:

```php
use Lucinda\Framework\AbstractCacheable;

final class ArticleCacheable extends AbstractCacheable
{
    protected function setTime(): void
    {
        $this->lastModifiedTime = 1700000000;
    }

    protected function setEtag(): void
    {
        $this->etag = sha1(
            $this->request->getURI()->getPage()."#".$this->lastModifiedTime
        );
    }
}
```

The base constructor is final. Implementations initialize cache values only through `setTime()` and `setEtag()`; `setTime()` always runs first. `CacheableFinder` validates that the configured class extends `AbstractCacheable` and returns its initialized `Lucinda\Headers\Cacheable` instance.

## Error reporting

`AbstractReporter` maps STDERR error types to logging severity:

| Error type | Logger method |
| --- | --- |
| `SERVER` | `emergency()` |
| `SYNTAX` | `alert()` |
| `LOGICAL` | `critical()` |
| `CLIENT` or unclassified | `error()` |
| `NONE` | Not logged |

Applications provide the logger and may decide that selected error types should not be logged:

```php
use Lucinda\Framework\AbstractReporter;
use Lucinda\Logging\Logger;
use Lucinda\STDERR\ErrorType;

final class FileReporter extends AbstractReporter
{
    protected function isLoggable(ErrorType $errorType): bool
    {
        return $errorType !== ErrorType::CLIENT;
    }

    protected function getLogger(): Logger
    {
        return new App\Logging\FileLogger($this->xml);
    }
}
```

`NONE` represents handled exceptional control flow rather than a reportable failure. The logging decision is made before constructing the logger.

## Utilities

### JSON

`Json` wraps native JSON processing with exception-based failures:

```php
use Lucinda\Framework\Json;

$json = new Json();
$encoded = $json->encode(["message" => "Bună ziua"]);
$decoded = $json->decode($encoded);
```

Encoding preserves Unicode. Decoding returns associative arrays and preserves integer literals outside PHP's integer range as strings. Malformed documents and unencodable values throw `JsonException`.

### Service registry

`ServiceRegistry` stores process-wide service objects by identifier:

```php
use Lucinda\Framework\ServiceRegistry;

ServiceRegistry::set("mailer", $mailer);
$mailer = ServiceRegistry::get("mailer");

ServiceRegistry::clear("mailer");
ServiceRegistry::clear();
```

Setting an existing identifier replaces its value. Looking up an unknown identifier throws `RuntimeException`. Long-running workers should clear request-scoped services explicitly.

## Failure boundaries

| Component | Failure behavior |
| --- | --- |
| `CacheableFinder` | Throws `Lucinda\MVC\ConfigurationException` for a missing or incompatible cacheable class. |
| `SqlConnectionProvider` | Throws SQL configuration, lookup, or connection exceptions. |
| `NoSqlDriverProvider` | Throws NoSQL configuration or connection exceptions. |
| OAuth2 adapters | Propagate client-driver failures; duplicate providers throw `LogicException`. |
| `Json` | Throws native `JsonException`. |
| `ServiceRegistry` | Throws `RuntimeException` for an unknown identifier. |

Do not convert infrastructure or adapter failures into successful requests. Handle them at the corresponding application boundary.

## Development

Install dependencies and run the test suite:

```bash
composer install
php test.php
```

From Windows, run PHP through WSL:

```powershell
wsl -d Ubuntu --cd /home/aherne/framework/framework-engine php test.php
```

Most tests use local fixtures and test doubles. Atomic NoSQL tests require
the PHP redis, memcached and apcu extensions, plus the redis-server and
memcached executables. They start private servers on temporary loopback ports
and verify the server process before accessing data. APCu runs in a child PHP
runtime with apc.enable_cli enabled, so the parent CLI configuration needs no
change. No application cache endpoint or database is used.

SQL tests require the PHP pdo_mysql extension and a MySQL server installation.
The fixture finds `mysqld` through `PATH`, with `/usr/sbin/mysqld` as a Linux
fallback. On macOS, ensure the MySQL bin directory is on `PATH` (for example,
`/opt/homebrew/bin` for Homebrew on Apple Silicon). It initializes a private
temporary schema and starts its own server using a Unix socket with networking
disabled. The server and its files are removed when the PHP process exits.

The atomic tests are compatible with the normal php test.php runner and run
the shared contract for Redis, Memcached and APCu. The Valkey driver is checked
against a private Redis-protocol endpoint. Server processes are terminated
when their test sessions close.

## License

MIT.
