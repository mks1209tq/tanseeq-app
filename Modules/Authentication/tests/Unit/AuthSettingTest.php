<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Entities\AuthSetting;

uses(RefreshDatabase::class);

it('can get a setting value', function () {
    AuthSetting::create([
        'key' => 'test_setting',
        'value' => 'test_value',
        'type' => 'string',
        'description' => 'Test Setting',
    ]);

    $value = AuthSetting::get('test_setting');

    expect($value)->toBe('test_value');
});

it('returns default value when setting does not exist', function () {
    $value = AuthSetting::get('non_existent', 'default_value');

    expect($value)->toBe('default_value');
});

it('can set a string setting', function () {
    AuthSetting::set('test_setting', 'test_value', 'string', 'Test Setting');

    $setting = AuthSetting::where('key', 'test_setting')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('test_value');
    expect($setting->type)->toBe('string');
});

it('can set a boolean setting', function () {
    AuthSetting::set('test_boolean', true, 'boolean', 'Test Boolean');

    $setting = AuthSetting::where('key', 'test_boolean')->first();
    expect($setting->value)->toBeTrue();
    expect($setting->attributes['value'])->toBe('1');

    AuthSetting::set('test_boolean', false, 'boolean');
    $setting->refresh();
    expect($setting->value)->toBeFalse();
    expect($setting->attributes['value'])->toBe('0');
});

it('can set an integer setting', function () {
    AuthSetting::set('test_integer', 42, 'integer', 'Test Integer');

    $setting = AuthSetting::where('key', 'test_integer')->first();
    expect($setting->value)->toBe(42);
    expect($setting->attributes['value'])->toBe('42');
});

it('can set a json setting', function () {
    $jsonData = ['key1' => 'value1', 'key2' => 'value2'];
    AuthSetting::set('test_json', $jsonData, 'json', 'Test JSON');

    $setting = AuthSetting::where('key', 'test_json')->first();
    expect($setting->value)->toBe($jsonData);
    expect($setting->attributes['value'])->toBe(json_encode($jsonData));
});

it('updates existing setting when setting again', function () {
    AuthSetting::set('test_setting', 'original', 'string');
    AuthSetting::set('test_setting', 'updated', 'string');

    $count = AuthSetting::where('key', 'test_setting')->count();
    expect($count)->toBe(1);

    $setting = AuthSetting::where('key', 'test_setting')->first();
    expect($setting->value)->toBe('updated');
});

it('checks if boolean setting is enabled', function () {
    AuthSetting::set('enabled_setting', true, 'boolean');
    AuthSetting::set('disabled_setting', false, 'boolean');

    expect(AuthSetting::isEnabled('enabled_setting'))->toBeTrue();
    expect(AuthSetting::isEnabled('disabled_setting'))->toBeFalse();
    expect(AuthSetting::isEnabled('non_existent', true))->toBeTrue();
    expect(AuthSetting::isEnabled('non_existent', false))->toBeFalse();
});

it('casts boolean value correctly', function () {
    $setting = AuthSetting::create([
        'key' => 'test_boolean',
        'value' => '1',
        'type' => 'boolean',
    ]);

    expect($setting->value)->toBeTrue();

    $setting->update(['value' => '0']);
    expect($setting->fresh()->value)->toBeFalse();
});

it('casts integer value correctly', function () {
    $setting = AuthSetting::create([
        'key' => 'test_integer',
        'value' => '42',
        'type' => 'integer',
    ]);

    expect($setting->value)->toBe(42);
});

it('casts json value correctly', function () {
    $jsonData = ['key' => 'value'];
    $setting = AuthSetting::create([
        'key' => 'test_json',
        'value' => json_encode($jsonData),
        'type' => 'json',
    ]);

    expect($setting->value)->toBe($jsonData);
});

it('handles null value', function () {
    $setting = AuthSetting::create([
        'key' => 'test_null',
        'value' => null,
        'type' => 'string',
    ]);

    expect($setting->value)->toBeNull();
});

