<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Entities\AuthSetting;
use Modules\Authentication\Entities\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can view settings page', function () {
    $response = $this->get(route('authentication.settings.edit'));

    $response->assertSuccessful();
    $response->assertViewIs('authentication::admin.settings.edit');
});

it('can update settings', function () {
    $response = $this->put(route('authentication.settings.update'), [
        'require_email_verification' => true,
        'force_two_factor' => false,
        'allow_registration' => true,
        'session_timeout' => 120,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect(AuthSetting::isEnabled('require_email_verification'))->toBeTrue();
    expect(AuthSetting::isEnabled('force_two_factor'))->toBeFalse();
    expect(AuthSetting::isEnabled('allow_registration'))->toBeTrue();
    expect(AuthSetting::get('session_timeout'))->toBe(120);
});

it('handles unchecked checkboxes correctly', function () {
    // Set to true first
    AuthSetting::set('allow_registration', true, 'boolean');

    // Submit form without checkbox (unchecked)
    $response = $this->put(route('authentication.settings.update'), [
        'require_email_verification' => true,
        'session_timeout' => 120,
        // allow_registration is not in the request (unchecked)
    ]);

    $response->assertRedirect();

    // Should be false now
    expect(AuthSetting::isEnabled('allow_registration'))->toBeFalse();
});

it('validates integer settings', function () {
    $response = $this->put(route('authentication.settings.update'), [
        'session_timeout' => 'not_an_integer',
    ]);

    $response->assertSessionHasErrors('session_timeout');
});

it('requires authentication to access settings', function () {
    auth()->logout();

    $response = $this->get(route('authentication.settings.edit'));

    $response->assertRedirect(route('login'));
});

