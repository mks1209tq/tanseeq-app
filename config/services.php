<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for microservices. Set 'mode' to 'monolith' for current
    | monolithic setup, or 'microservice' for distributed services.
    |
    */

    'authentication' => [
        'mode' => env('AUTHENTICATION_SERVICE_MODE', 'monolith'),
        'url' => env('AUTHENTICATION_SERVICE_URL', 'http://authentication-service.test'),
        'timeout' => env('AUTHENTICATION_SERVICE_TIMEOUT', 5),
    ],

    'authorization' => [
        'mode' => env('AUTHORIZATION_SERVICE_MODE', 'monolith'),
        'url' => env('AUTHORIZATION_SERVICE_URL', 'http://authorization-service.test'),
        'timeout' => env('AUTHORIZATION_SERVICE_TIMEOUT', 5),
    ],

    'todo' => [
        'mode' => env('TODO_SERVICE_MODE', 'monolith'),
        'url' => env('TODO_SERVICE_URL', 'http://todo-service.test'),
        'timeout' => env('TODO_SERVICE_TIMEOUT', 5),
    ],
];
