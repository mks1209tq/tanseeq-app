<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ConfigTransports\Concerns\IsTransportable;

uses(RefreshDatabase::class);

// Create a test model using the trait
class TestTransportableModel extends \Illuminate\Database\Eloquent\Model
{
    use IsTransportable;

    protected $table = 'test_models';

    protected $fillable = ['code', 'name', 'description'];
}

it('provides default transport object type from table name', function () {
    expect(TestTransportableModel::getTransportObjectType())->toBe('test_models');
});

it('provides default identifier from code attribute', function () {
    $model = new TestTransportableModel(['code' => 'TEST_CODE']);

    expect($model->getTransportIdentifier())->toBe('TEST_CODE');
});

it('provides default identifier from name attribute when code not present', function () {
    $model = new TestTransportableModel(['name' => 'Test Name']);

    expect($model->getTransportIdentifier())->toBe('Test Name');
});

it('provides default identifier from id when no natural key present', function () {
    $model = new TestTransportableModel(['id' => 123]);

    expect($model->getTransportIdentifier())->toBe(123);
});

it('provides default transport payload excluding ids and timestamps', function () {
    $model = new TestTransportableModel([
        'id' => 1,
        'code' => 'TEST_CODE',
        'name' => 'Test Name',
        'description' => 'Test Description',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payload = $model->toTransportPayload();

    expect($payload)->toHaveKey('code');
    expect($payload)->toHaveKey('name');
    expect($payload)->toHaveKey('description');
    expect($payload)->not->toHaveKey('id');
    expect($payload)->not->toHaveKey('created_at');
    expect($payload)->not->toHaveKey('updated_at');
});

it('provides empty dependencies by default', function () {
    $model = new TestTransportableModel();

    expect($model->getTransportDependencies())->toBe([]);
});

