<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Service-Specific Database Connections
        |--------------------------------------------------------------------------
        |
        | Separate database connections for each microservice module.
        | These can point to separate databases for true separation, or
        | the same database with different connection names for organization.
        |
        */

        'authentication' => [
            'driver' => env('AUTHENTICATION_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('AUTHENTICATION_DB_URL'),
            'host' => env('AUTHENTICATION_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('AUTHENTICATION_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('AUTHENTICATION_DB_DATABASE', base_path('tenants/1/authentication.sqlite')),
            'username' => env('AUTHENTICATION_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('AUTHENTICATION_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('AUTHENTICATION_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('AUTHENTICATION_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('AUTHENTICATION_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('AUTHENTICATION_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'authorization' => [
            'driver' => env('AUTHORIZATION_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('AUTHORIZATION_DB_URL'),
            'host' => env('AUTHORIZATION_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('AUTHORIZATION_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('AUTHORIZATION_DB_DATABASE', base_path('Modules/Authorization/database/authorization.sqlite')),
            'username' => env('AUTHORIZATION_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('AUTHORIZATION_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('AUTHORIZATION_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('AUTHORIZATION_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('AUTHORIZATION_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('AUTHORIZATION_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'company' => [
            'driver' => env('COMPANY_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('COMPANY_DB_URL'),
            'host' => env('COMPANY_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('COMPANY_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('COMPANY_DB_DATABASE', base_path('tenants/1/company.sqlite')),
            'username' => env('COMPANY_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('COMPANY_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('COMPANY_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('COMPANY_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('COMPANY_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('COMPANY_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'clipboard' => [
            'driver' => env('CLIPBOARD_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('CLIPBOARD_DB_URL'),
            'host' => env('CLIPBOARD_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('CLIPBOARD_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('CLIPBOARD_DB_DATABASE', base_path('tenants/1/clipboard.sqlite')),
            'username' => env('CLIPBOARD_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('CLIPBOARD_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('CLIPBOARD_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('CLIPBOARD_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('CLIPBOARD_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('CLIPBOARD_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'todo' => [
            'driver' => env('TODO_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('TODO_DB_URL'),
            'host' => env('TODO_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('TODO_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('TODO_DB_DATABASE', base_path('Modules/Todo/database/todo.sqlite')),
            'username' => env('TODO_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('TODO_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('TODO_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('TODO_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('TODO_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('TODO_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'config_transports' => [
            'driver' => env('CONFIG_TRANSPORTS_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('CONFIG_TRANSPORTS_DB_URL'),
            'host' => env('CONFIG_TRANSPORTS_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('CONFIG_TRANSPORTS_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('CONFIG_TRANSPORTS_DB_DATABASE', base_path('Modules/ConfigTransports/database/config_transports.sqlite')),
            'username' => env('CONFIG_TRANSPORTS_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('CONFIG_TRANSPORTS_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('CONFIG_TRANSPORTS_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('CONFIG_TRANSPORTS_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('CONFIG_TRANSPORTS_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('CONFIG_TRANSPORTS_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'system' => [
            'driver' => env('SYSTEM_DB_DRIVER', env('DB_CONNECTION', 'sqlite')),
            'url' => env('SYSTEM_DB_URL'),
            'host' => env('SYSTEM_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('SYSTEM_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('SYSTEM_DB_DATABASE', database_path('system.sqlite')),
            'username' => env('SYSTEM_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('SYSTEM_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('SYSTEM_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('SYSTEM_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('SYSTEM_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('SYSTEM_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
