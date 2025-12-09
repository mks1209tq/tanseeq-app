<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Environment Role
    |--------------------------------------------------------------------------
    |
    | Determines the role of this environment in the transport system.
    | - 'dev': Development environment where changes are made and recorded
    | - 'qa': Quality assurance environment (read-only except via imports)
    | - 'prod': Production environment (read-only except via imports)
    |
    | This is automatically mapped from APP_ENV:
    | - local, development -> dev
    | - staging, testing -> qa
    | - production -> prod
    |
    */

    'environment_role' => env('SYSTEM_ENVIRONMENT_ROLE', function () {
        $appEnv = env('APP_ENV', 'production');

        return match ($appEnv) {
            'local', 'development' => 'dev',
            'staging', 'testing' => 'qa',
            'production' => 'prod',
            default => 'dev',
        };
    }),

    /*
    |--------------------------------------------------------------------------
    | Transport Edit Protection
    |--------------------------------------------------------------------------
    |
    | Whether direct edits to transportable models are allowed.
    | In QA/PROD, this should be true to enforce transport-only changes.
    |
    */

    'transport_edit_protection' => env('SYSTEM_TRANSPORT_EDIT_PROTECTION', function () {
        $role = config('system.environment_role', 'dev');

        return $role !== 'dev';
    }),

    /*
    |--------------------------------------------------------------------------
    | Conflict Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | Default strategy for handling conflicts when importing transports.
    | Options: 'update', 'skip', 'fail'
    |
    | Can be overridden per object type in transport config.
    |
    */

    'default_conflict_resolution' => env('SYSTEM_DEFAULT_CONFLICT_RESOLUTION', 'update'),
];

