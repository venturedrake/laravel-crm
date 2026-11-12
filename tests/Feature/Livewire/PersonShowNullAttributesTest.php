<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationShow;
use VentureDrake\LaravelCrm\Livewire\People\PersonShow;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Setting;

/*
 * Regression cover for #125: the show views passed nullable columns
 * (person gender/birthday, phone/email type) straight to ucfirst(), which
 * is a deprecation on PHP 8.1+ and a TypeError from PHP 9.
 *
 * withoutDeprecationHandling() can't be used here: on PHP 8.5 the test
 * tooling itself (Pest, Collision, Whoops) trips setAccessible()
 * deprecations first. Instead, collect only the deprecations raised from
 * compiled Blade views while the component renders.
 */

function viewDeprecationsDuring(callable $callback): array
{
    $deprecations = [];
    $previous = set_error_handler(function ($level, $message, $file, $line) use (&$deprecations, &$previous) {
        if (str_contains(str_replace('\\', '/', $file), 'storage/framework/views/')) {
            $deprecations[] = $message;

            return true;
        }

        return $previous ? $previous($level, $message, $file, $line) : false;
    }, E_DEPRECATED | E_USER_DEPRECATED);

    try {
        $callback();
    } finally {
        restore_error_handler();
    }

    return $deprecations;
}

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
});

it('renders a person with no gender, birthday or contact types', function () {
    $person = Person::create(['first_name' => 'No Gender']);
    $person->phones()->create(['number' => '+61400000000', 'type' => null, 'primary' => true]);
    $person->emails()->create(['address' => 'nogender@example.com', 'type' => null, 'primary' => true]);

    expect($person->gender)->toBeNull()
        ->and($person->birthday)->toBeNull();

    $deprecations = viewDeprecationsDuring(fn () => Livewire::test(PersonShow::class, ['person' => $person])
        ->assertOk()
        ->assertSee('No Gender')
        ->assertSee('+61400000000')
        ->assertSee('nogender@example.com'));

    expect($deprecations)->toBe([]);
});

it('formats the birthday with the date format setting', function () {
    Setting::updateOrCreate(['name' => 'date_format'], ['value' => 'd/m/Y']);
    app('laravel-crm.settings')->forgetCache();

    $person = Person::create(['first_name' => 'Has Birthday', 'birthday' => '2000-03-15']);

    $html = Livewire::test(PersonShow::class, ['person' => $person])->html();

    expect($html)->toContain('15/03/2000')
        ->not->toContain('2000-03-15 00:00:00');
});

it('renders an organization with no contact types', function () {
    $organization = Organization::create(['name' => 'No Types Co']);
    $organization->phones()->create(['number' => '+61400000001', 'type' => null, 'primary' => true]);
    $organization->emails()->create(['address' => 'notypes@example.com', 'type' => null, 'primary' => true]);

    $deprecations = viewDeprecationsDuring(fn () => Livewire::test(OrganizationShow::class, ['organization' => $organization])
        ->assertOk()
        ->assertSee('No Types Co')
        ->assertSee('+61400000001')
        ->assertSee('notypes@example.com'));

    expect($deprecations)->toBe([]);
});
