<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | How to identify the tenant for each request:
    | - domain: Match by full domain (e.g., tenant1.example.com)
    | - subdomain: Match by subdomain (e.g., tenant1.example.com)
    | - header: Match by X-Tenant-ID header
    | - session: Match by session tenant_id
    | - path: Match by URL path (e.g., /tenant/1/...)
    |
    | You can specify multiple strategies, they will be tried in order.
    |
    */

    'resolution_strategy' => [
        'subdomain',
        'domain',
        'header',
        'session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Path
    |--------------------------------------------------------------------------
    |
    | Base path where tenant databases are stored.
    |
    */

    'database_path' => base_path('tenants'),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant
    |--------------------------------------------------------------------------
    |
    | If no tenant is identified, use this as fallback (for development).
    | Set to null to require tenant identification.
    |
    */

    'default_tenant_id' => env('DEFAULT_TENANT_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Whether to enforce strict tenant isolation.
    | When true, cross-tenant queries are blocked.
    |
    */

    'strict_isolation' => env('TENANT_STRICT_ISOLATION', true),
];

