<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Entities\Role;
use Modules\ConfigTransports\Http\Middleware\TransportEditProtection;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('allows requests in dev environment', function () {
    config(['system.environment_role' => 'dev']);
    config(['system.transport_edit_protection' => false]);

    $middleware = new TransportEditProtection();

    $request = \Illuminate\Http\Request::create('/admin/authorization/roles/create', 'GET');
    $next = function ($req) {
        return response('OK');
    };

    $response = $middleware->handle($request, $next);

    expect($response->getContent())->toBe('OK');
});

it('blocks requests in qa environment when protection enabled', function () {
    config(['system.environment_role' => 'qa']);
    config(['system.transport_edit_protection' => true]);

    $middleware = new TransportEditProtection();

    $request = \Illuminate\Http\Request::create('/admin/authorization/roles/create', 'GET');
    $next = function ($req) {
        return response('OK');
    };

    $response = $middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(403);
});

it('blocks requests in prod environment when protection enabled', function () {
    config(['system.environment_role' => 'prod']);
    config(['system.transport_edit_protection' => true]);

    $middleware = new TransportEditProtection();

    $request = \Illuminate\Http\Request::create('/admin/authorization/roles/create', 'GET');
    $next = function ($req) {
        return response('OK');
    };

    $response = $middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(403);
});

it('allows requests when protection is disabled', function () {
    config(['system.environment_role' => 'qa']);
    config(['system.transport_edit_protection' => false]);

    $middleware = new TransportEditProtection();

    $request = \Illuminate\Http\Request::create('/admin/authorization/roles/create', 'GET');
    $next = function ($req) {
        return response('OK');
    };

    $response = $middleware->handle($request, $next);

    expect($response->getContent())->toBe('OK');
});

