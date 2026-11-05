<?php

/*
 * Contact emails and phone numbers are checked wherever they are captured.
 *
 * A person could be saved with the email "pat@@invalid" and the phone "zzz", and
 * a lead with "bad-email" and "abc-not-a-phone". The forms only required a `type`
 * alongside an address, and the lead form had no rules for these fields at all.
 *
 * The forms are mounted through render-stub subclasses, as in the authorization
 * suites, so the real rules run without the tables their blades reach for. The
 * invoice form is absent: it captures a name only and has no email or phone.
 */

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Http\Requests\StoreOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\StorePersonRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateInvoiceRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePersonRequest;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadCreate;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadEdit;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderCreate;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderEdit;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationCreate;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationEdit;
use VentureDrake\LaravelCrm\Livewire\People\PersonCreate;
use VentureDrake\LaravelCrm\Livewire\People\PersonEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteEdit;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

class ContactPersonCreate extends PersonCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactPersonEdit extends PersonEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactOrganizationCreate extends OrganizationCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactOrganizationEdit extends OrganizationEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactLeadCreate extends LeadCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactLeadEdit extends LeadEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactDealCreate extends DealCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactDealEdit extends DealEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactQuoteCreate extends QuoteCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactQuoteEdit extends QuoteEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactOrderCreate extends OrderCreate
{
    public function render()
    {
        return '<div></div>';
    }
}

class ContactOrderEdit extends OrderEdit
{
    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    Gate::before(fn () => true);

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);
    Setting::updateOrCreate(['name' => 'team'], ['value' => 'related']);
    app('laravel-crm.settings')->forgetCache();

    foreach ([Lead::class, Deal::class, Quote::class, Order::class] as $model) {
        $pipeline = Pipeline::create([
            'external_id' => Str::uuid()->toString(),
            'name' => class_basename($model).' Pipeline',
            'model' => $model,
        ]);
        $pipeline->pipelineStages()->create([
            'external_id' => Str::uuid()->toString(),
            'name' => 'First',
            'order' => 0,
        ]);
    }
});

dataset('list forms', [
    'person create' => fn () => [ContactPersonCreate::class, []],
    'person edit' => fn () => [ContactPersonEdit::class, ['person' => Person::create(['first_name' => 'Pat'])]],
    'organization create' => fn () => [ContactOrganizationCreate::class, []],
    'organization edit' => fn () => [ContactOrganizationEdit::class, ['organization' => Organization::create(['name' => 'Acme'])]],
]);

dataset('inline forms', [
    'lead create' => fn () => [ContactLeadCreate::class, []],
    'lead edit' => fn () => [ContactLeadEdit::class, ['lead' => Lead::create(['title' => 'A lead', 'currency' => 'USD'])]],
    'deal create' => fn () => [ContactDealCreate::class, []],
    'deal edit' => fn () => [ContactDealEdit::class, ['deal' => Deal::create(['title' => 'A deal', 'currency' => 'USD'])]],
    'quote create' => fn () => [ContactQuoteCreate::class, []],
    'quote edit' => fn () => [ContactQuoteEdit::class, ['quote' => Quote::create(['title' => 'A quote', 'currency' => 'USD'])]],
    'order create' => fn () => [ContactOrderCreate::class, []],
    'order edit' => fn () => [ContactOrderEdit::class, ['order' => Order::create(['currency' => 'USD'])]],
]);

dataset('valid phones', ['+61 2 9999 9999', '(02) 9999-9999', '0412 345 678 ext 12']);

it('rejects an invalid email and phone in the contact lists', function (array $form) {
    [$component, $params] = $form;

    Livewire::test($component, $params)
        ->set('emails', [['address' => 'pat@@invalid', 'type' => 'work']])
        ->set('phones', [['number' => 'zzz', 'type' => 'work']])
        ->call('save')
        ->assertHasErrors(['emails.0.address', 'phones.0.number']);
})->with('list forms');

it('accepts a valid email and phone in the contact lists', function (array $form, string $phone) {
    [$component, $params] = $form;

    Livewire::test($component, $params)
        ->set('emails', [['id' => null, 'address' => 'pat@example.com', 'type' => 'work']])
        ->set('phones', [['id' => null, 'number' => $phone, 'type' => 'work']])
        ->call('save')
        ->assertHasNoErrors(['emails.0.address', 'phones.0.number']);
})->with('list forms')->with('valid phones');

it('rejects an invalid email and phone on an inline contact', function (array $form) {
    [$component, $params] = $form;

    Livewire::test($component, $params)
        ->set('email', 'bad-email')
        ->set('phone', 'abc-not-a-phone')
        ->call('save')
        ->assertHasErrors(['email', 'phone']);
})->with('inline forms');

it('accepts a valid email and phone on an inline contact', function (array $form, string $phone) {
    [$component, $params] = $form;

    Livewire::test($component, $params)
        ->set('email', 'pat@example.com')
        ->set('phone', $phone)
        ->call('save')
        ->assertHasNoErrors(['email', 'phone']);
})->with('inline forms')->with('valid phones');

it('leaves both optional', function (array $form) {
    [$component, $params] = $form;

    Livewire::test($component, $params)
        ->set('email', '')
        ->set('phone', '')
        ->call('save')
        ->assertHasNoErrors(['email', 'phone']);
})->with('inline forms');

it('saves nothing when a new person is invalid', function () {
    Livewire::test(ContactPersonCreate::class)
        ->set('first_name', 'Pat')
        ->set('emails', [['address' => 'pat@@invalid', 'type' => 'work']])
        ->set('phones', [['number' => 'zzz', 'type' => 'work']])
        ->call('save');

    expect(Person::count())->toBe(0);
});

it('applies the same rules to the web requests behind the older forms', function (string $request, string $email, string $phone) {
    $rules = array_intersect_key((new $request)->rules(), array_flip([$email, $phone]));

    expect($rules)->toHaveCount(2);

    $data = fn (string $emailValue, string $phoneValue) => str_contains($email, '*')
        ? ['emails' => [['address' => $emailValue]], 'phones' => [['number' => $phoneValue]]]
        : ['email' => $emailValue, 'phone' => $phoneValue];

    expect(Validator::make($data('bad-email', 'zzz'), $rules)->errors()->count())->toBe(2)
        ->and(Validator::make($data('pat@example.com', '+61 2 9999 9999'), $rules)->passes())->toBeTrue();
})->with([
    'store person' => [StorePersonRequest::class, 'emails.*.address', 'phones.*.number'],
    'update person' => [UpdatePersonRequest::class, 'emails.*.address', 'phones.*.number'],
    'store order' => [StoreOrderRequest::class, 'email', 'phone'],
    'update order' => [UpdateOrderRequest::class, 'email', 'phone'],
    'update invoice' => [UpdateInvoiceRequest::class, 'email', 'phone'],
]);

describe('the REST API', function () {
    beforeEach(function () {
        $user = User::create([
            'name' => 'Api', 'email' => 'contact-api@example.com', 'password' => bcrypt('secret'), 'crm_access' => true,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken,
            'Accept' => 'application/json',
        ]);
    });

    dataset('endpoints', [
        'people' => ['people', ['first_name' => 'Pat']],
        'organizations' => ['organizations', ['name' => 'Acme']],
        'leads' => ['leads', ['title' => 'A lead']],
        'deals' => ['deals', ['title' => 'A deal']],
    ]);

    function apiPath(string $resource, ?string $id = null): string
    {
        return '/crm/api/v2/'.$resource.($id ? '/'.$id : '');
    }

    it('rejects an invalid email and phone on create', function (string $resource, array $body) {
        $this->postJson(apiPath($resource), [...$body, 'email' => 'pat@@invalid', 'phone' => 'zzz'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone']);
    })->with('endpoints');

    it('rejects an invalid email and phone in the list shape', function (string $resource, array $body) {
        $this->postJson(apiPath($resource), [
            ...$body,
            'emails' => [['address' => 'bad-email']],
            'phones' => [['number' => 'abc-not-a-phone']],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['emails.0.address', 'phones.0.number']);
    })->with('endpoints');

    it('rejects an invalid email and phone on update', function (string $resource, array $body) {
        $id = $this->postJson(apiPath($resource), $body)->assertCreated()->json('data.id');

        $this->patchJson(apiPath($resource, $id), ['email' => 'pat@@invalid', 'phone' => 'zzz'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone']);
    })->with('endpoints');

    it('accepts a valid email and phone', function (string $resource, array $body, string $phone) {
        $this->postJson(apiPath($resource), [...$body, 'email' => 'pat@example.com', 'phone' => $phone])
            ->assertCreated();
    })->with('endpoints')->with('valid phones');
});
