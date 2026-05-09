<?php

use function VentureDrake\LaravelCrm\Http\Helpers\PublicProperties\publicProperties;

class PublicPropertiesStub
{
    public string $name = 'Alice';

    public int $age = 30;

    protected string $secret = 'shh';

    private string $hidden = 'nope';
}

test('returns only public properties as key value pairs', function () {
    $obj = new PublicPropertiesStub;
    $props = publicProperties($obj);

    expect($props)->toHaveKey('name', 'Alice')
        ->toHaveKey('age', 30)
        ->not->toHaveKey('secret')
        ->not->toHaveKey('hidden');
});

test('returns empty array for object with no public properties', function () {
    $obj = new class
    {
        protected string $x = 'x';
    };

    expect(publicProperties($obj))->toBe([]);
});

test('returns all public properties from stdClass', function () {
    $obj = new stdClass;
    $obj->foo = 'bar';
    $obj->baz = 42;

    $props = publicProperties($obj);

    expect($props)->toHaveKey('foo', 'bar')
        ->toHaveKey('baz', 42);
});
