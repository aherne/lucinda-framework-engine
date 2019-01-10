# Lucinda Framework API

This is the engine behind Lucinda Framework, binding component APIs with contents of configuration.xml file and thus featuring following components:

- **CachingBinder**: binds HTTP Caching API with STDOUT MVC API and contents of *http_caching* XML tag. Performs HTTP cache validation and models Response accordingly.
- **NoSQLDataSourceBinder**: binds NoSQL Data Access API with MVC STDOUT API and contents of *servers*>*nosql* XML tag. Detects a data source (encapsulating connection information) that will insure a single connection per server is used later on when NoSQL server is queried
- **NoSQLDataSourceBinder**: binds NoSQL Data Access API with MVC STDOUT API and contents of *servers*>*nosql* XML tag. Detects a data source (encapsulating connection information) that will insure a single connection per server is used later on when NoSQL server is queried
- **LocalizationBinder**: binds Internationalization API with MVC STDOUT API and contents of *internationalization* XML tag. Allows developers to produce a localizable response via GETTEXT engine.
- **LoggingBinder**: binds Logging API with MVC STDOUT API and contents of *loggers* XML tag. Allows developers to log a message later on (eg: in a file or syslog)
- **SecurityBinder**: binds HTTP Security API & OAuth2 Client API with MVC STDOUT API and contents of *security* XML tag. Applies web security filter (eg: authentication and authorization) on a routed request and throws a SecurityPacket when response needs to be rerouted to another location (eg: login failed).
- **ViewLanguageBinder**: binds View Language API with MVC STDOUT API and contents of *application* XML tag. Compiles a templated HTML view and alters response accordingly.
- **ValidationBinder**: binds Parameters Validation API with MVC STDOUT API and contents of *routes* XML tag. Allows developers to create simple and elegant XML-based request/path parameters validation policies.

Each of "binders" above will be used by the framework in respective event listeners, promoting complete separation of framework and user level logic as well as allowing developers to update framework functionality in tandem with referenced APIs via *composer update*.

In addition to binders, engine defines blueprints necessary for developers to bind to in order to gain an ability in application:

- **AbstractLoggerWrapper**: defines blueprints for logging, requiring class that extends it to generate a Lucinda\Logging\Logger instance from matching XML &lt;logger&gt; tag
- **LogReporter**: defines blueprints for error reporting, binding Logging API with MVC STDERR API, requiring class that extends it to generate a Lucinda\Logging\Logger instance from matching XML &lt;reporter&gt; tag