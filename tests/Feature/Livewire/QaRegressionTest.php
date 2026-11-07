<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveMeeting;
use VentureDrake\LaravelCrm\Http\Livewire\LiveMeetings;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderCreate;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\People\PersonShow;
use VentureDrake\LaravelCrm\Livewire\Products\ProductIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

beforeEach(function () {
    $this->actingAsUser(['crm_access' => 1]);
    foreach (['currency' => 'USD', 'team' => 'related', 'timezone' => 'Australia/Sydney'] as $name => $value) {
        Setting::updateOrCreate(['name' => $name], ['value' => $value]);
    }
    app('laravel-crm.settings')->forgetCache();
    foreach ([Order::class, Quote::class, Lead::class, Deal::class, Invoice::class] as $model) {
        $pipeline = Pipeline::create(['name' => class_basename($model), 'model' => $model]);
        $pipeline->pipelineStages()->create(['name' => 'Draft', 'order' => 0]);
    }
    foreach (['Billing', 'Shipping'] as $name) {
        AddressType::firstOrCreate(['name' => $name]);
    }
});

it('creates an order from a person-only accepted quote with optional addresses', function (bool $organization, bool $addresses) {
    $person = Person::create(['first_name' => 'QA Contact']);
    $org = $organization ? Organization::create(['name' => 'QA Company']) : null;
    if ($addresses) {
        foreach (AddressType::all() as $type) {
            ($org ?? $person)->addresses()->create(['address_type_id' => $type->id, 'line1' => '1 Test Street', 'primary' => 1]);
        }
    }
    $quote = Quote::create(['title' => 'QA Conversion', 'person_id' => $person->id, 'organization_id' => $org?->id, 'currency' => 'USD', 'accepted_at' => now()]);
    Livewire::test(OrderCreate::class, ['fromModelType' => 'quote', 'fromModelId' => $quote->id])
        ->assertSet('person_id', $person->id)
        ->assertSet('organization_id', $org?->id)
        ->call('save')->assertHasNoErrors();
    expect(Order::first()->person_id)->toBe($person->id)
        ->and(Order::first()->organization_id)->toBe($org?->id)
        ->and(Order::first()->quote_id)->toBe($quote->id);
})->with([false, true])->with([false, true]);

it('resets filters with an overdue invoice while retaining mounted state', function () {
    Invoice::create(['currency' => 'USD', 'due_date' => now()->subDays(3), 'total' => 100, 'amount_due' => 100]);
    $component = Livewire::test(InvoiceIndex::class);
    $pipeline = $component->get('pipeline');
    $component->set('search', 'missing')->set('user_id', [auth()->id()])->set('showFilters', true)
        ->call('clear')->assertOk()->assertSet('timezone', 'Australia/Sydney')
        ->assertSet('search', '')->assertSet('user_id', [])->assertSet('showFilters', false);
    expect($component->get('pipeline')->id)->toBe($pipeline->id)
        ->and($component->viewData('invoices')->total())->toBe(1);
});

it('executes every advertised document sort against real column names', function (string $class, string $method, string $model) {
    expect(Schema::hasColumn('crm_deliveries', 'reference'))->toBeFalse();
    $this->actingAsUserWithPermissions(['view crm orders', 'view crm quotes']);
    $component = app($class);
    $component->mount();
    foreach ($component->headers() as $header) {
        if ($header['sortable'] ?? true) {
            // SQLite can silently treat an unknown quoted identifier as a string.
            expect(Schema::hasColumn((new $model)->getTable(), $header['key']))->toBeTrue($header['key']);
            foreach (['asc', 'desc'] as $direction) {
                $component->sortBy = ['column' => $header['key'], 'direction' => $direction];
                $component->$method();
            }
        }
    }
    foreach (['reference', 'order', 'quote', 'labels', 'bogus'] as $column) {
        $component->sortBy = ['column' => $column, 'direction' => 'asc'];
        $component->$method();
    }
})->with([
    [DeliveryIndex::class, 'deliveries', Delivery::class],
    [InvoiceIndex::class, 'invoices', Invoice::class],
    [OrderIndex::class, 'orders', Order::class],
    [PurchaseOrderIndex::class, 'purchaseOrders', PurchaseOrder::class],
]);

it('renders delivery order details and reads them without per-row queries', function () {
    $person = Person::create(['first_name' => 'QA Delivery Contact']);
    $org = Organization::create(['name' => 'QA Delivery Organization']);
    $order = Order::create(['person_id' => $person->id, 'organization_id' => $org->id, 'reference' => 'QA-DELIVERY-REF', 'currency' => 'USD']);
    foreach (range(1, 3) as $i) {
        Delivery::create(['order_id' => $order->id]);
    }
    Livewire::test(DeliveryIndex::class)->assertSee('QA Delivery Contact')->assertSee('QA Delivery Organization')->assertSee('QA-DELIVERY-REF');
    $component = app(DeliveryIndex::class);
    $rows = $component->deliveries();
    DB::enableQueryLog();
    DB::flushQueryLog();
    foreach ($rows as $row) {
        foreach ($component->headers() as $header) {
            data_get($row, $header['key']);
        }
    }
    expect(DB::getQueryLog())->toBe([]);
    DB::disableQueryLog();
});

it('preserves custom titles across typing and suggestion selection', function (string $entity) {
    $class = 'VentureDrake\\LaravelCrm\\Livewire\\'.$entity.'s\\'.$entity.'Create';
    $person = Person::create(['first_name' => 'Selected Person']);
    $org = Organization::create(['name' => 'Selected Organization']);
    Livewire::test($class)->set('title', 'My custom title')
        ->set('person_name', 'Typed Person')->assertSet('title', 'My custom title')
        ->call('linkPerson', $person->id)->assertSet('title', 'My custom title')
        ->set('organization_name', 'Typed Organization')->assertSet('title', 'My custom title')
        ->call('linkOrganization', $org->id)->assertSet('title', 'My custom title');
    Livewire::test($class)->set('person_name', 'First')->assertSet('title', 'First '.$entity)
        ->set('person_name', 'Second')->assertSet('title', 'Second '.$entity)
        ->set('organization_name', 'Company')->assertSet('title', 'Company '.$entity)
        ->set('organization_name', '')->assertSet('title', 'Second '.$entity)
        ->set('title', 'Custom after generation')->set('person_name', 'Third')->assertSet('title', 'Custom after generation');
})->with(['Quote', 'Lead', 'Deal']);

it('rejects invalid activity dates on create and edit without writing', function (string $entity, string $start, string $finish, string $field, string $rule) {
    $plural = $entity === 'Lunch' ? 'Lunches' : $entity.'s';
    $modelClass = 'VentureDrake\\LaravelCrm\\Models\\'.$entity;
    $related = 'VentureDrake\\LaravelCrm\\Livewire\\'.$plural.'\\'.$entity.'Related';
    $item = 'VentureDrake\\LaravelCrm\\Livewire\\'.$plural.'\\'.$entity.'Item';
    $person = Person::create(['first_name' => 'Scheduling parent']);
    Livewire::test($related, ['model' => $person])->set('name', 'Invalid schedule')
        ->set('start_at', $start)->set('finish_at', $finish)->call('save')
        ->assertHasErrors([$field => $rule]);
    expect($modelClass::count())->toBe(0);
    $record = $person->{strtolower($plural)}()->create(['name' => 'Original', 'start_at' => '2026-11-08 10:00:00', 'finish_at' => '2026-11-08 11:00:00']);
    Livewire::test($item, [strtolower($entity) => $record])->call('edit')->set('start_at', $start)->set('finish_at', $finish)
        ->call('update')->assertHasErrors([$field => $rule]);
    expect($record->fresh()->finish_at->format('H:i'))->toBe('11:00');
})->with(['Meeting', 'Call', 'Lunch'])->with([
    ['2026-11-08T10:00', '2026-11-08T09:00', 'finish_at', 'after_or_equal'],
    ['invalid', '2026-11-08T11:00', 'start_at', 'date'],
    ['2026-11-08T10:00', 'invalid', 'finish_at', 'date'],
]);

it('renders person and custom field headings escaped exactly once', function () {
    $text = "QA O'Connor & <script>alert(1)</script>";
    $person = Person::create(['first_name' => $text]);
    $html = Livewire::test(PersonShow::class, ['person' => $person])->html();
    expect($html)->toContain(e($text))->not->toContain(e(e($text)))->not->toContain('<script>alert(1)</script>');
    $group = FieldGroup::create(['name' => $text]);
    $field = Field::create(['name' => 'QA field', 'type' => 'text', 'field_group_id' => $group->id]);
    FieldModel::create(['field_id' => $field->id, 'model' => Person::class]);
    $person->fields()->create(['field_id' => $field->id, 'value' => 'Test']);
    view()->share('errors', new ViewErrorBag);
    foreach (['custom-fields', 'custom-field-values'] as $component) {
        $html = Blade::render('<x-crm-'.$component.' :model="$person" :group="true" />', ['person' => $person->fresh()]);
        expect($html)->toContain(e($text))->not->toContain(e(e($text)))->not->toContain('<script>alert(1)</script>');
    }
});

it('keeps mounted product display settings when clearing filters', function () {
    $component = Livewire::test(ProductIndex::class);
    $currency = $component->get('currency');
    $dateFormat = $component->get('dateFormat');
    $component->set('search', 'test')->set('showFilters', true)->call('clear')->assertOk()
        ->assertSet('currency', $currency)->assertSet('dateFormat', $dateFormat)->assertSet('search', '');
});

it('accepts equal and later activity finish times on create and edit', function (string $entity, string $finish) {
    $plural = $entity === 'Lunch' ? 'Lunches' : $entity.'s';
    $related = 'VentureDrake\\LaravelCrm\\Livewire\\'.$plural.'\\'.$entity.'Related';
    $item = 'VentureDrake\\LaravelCrm\\Livewire\\'.$plural.'\\'.$entity.'Item';
    $person = Person::create(['first_name' => 'Scheduling parent']);
    Livewire::test($related, ['model' => $person])->set('name', 'Valid schedule')
        ->set('start_at', '2026-11-08T10:00')->set('finish_at', $finish)->call('save')->assertHasNoErrors();
    $record = $person->{strtolower($plural)}()->firstOrFail();
    Livewire::test($item, [strtolower($entity) => $record])->call('edit')->set('finish_at', $finish)
        ->call('update')->assertHasNoErrors();
    expect($record->fresh()->finish_at->format('Y-m-d\TH:i'))->toBe($finish);
})->with(['Meeting', 'Call', 'Lunch'])->with(['2026-11-08T10:00', '2026-11-09T09:00']);

it('disables unsupported related and settings table sorts', function (string $name, array $keys) {
    $component = app('VentureDrake\\LaravelCrm\\Livewire\\'.$name);
    $headers = collect($component->headers())->keyBy('key');
    foreach ($keys as $key) {
        expect($headers[$key]['sortable'] ?? true)->toBeFalse($name.' '.$key);
    }
})->with([
    ['Products\\ProductIndex', ['productCategory.name', 'price', 'taxRate.name', 'taxRate.rate']],
    ['Teams\\TeamIndex', ['users']],
    ['Settings\\TaxRates\\TaxRateIndex', ['products']],
    ['Settings\\ProductCategories\\ProductCategoryIndex', ['products']],
    ['Settings\\Permissions\\RoleIndex', ['users']],
    ['Settings\\CustomFieldGroups\\CustomFieldGroupIndex', ['fields']],
    ['Settings\\PipelineStages\\PipelineStageIndex', ['pipeline.name']],
    ['Orders\\OrderRelatedIndex', ['labels']],
]);

class QaLegacyMeeting extends LiveMeeting
{
    public function mount(Meeting $meeting, $view = 'meeting')
    {
        // The legacy mount reads an obsolete settings API; validation is in update().
        $this->meeting = $meeting;
        $this->name = $meeting->name;
    }

    public function render()
    {
        return '<div></div>';
    }
}

class QaLegacyMeetings extends LiveMeetings
{
    public function render()
    {
        return '<div></div>';
    }
}

it('rejects reverse chronology in registered legacy meeting actions', function () {
    $person = Person::create(['first_name' => 'Legacy scheduling']);
    Livewire::test(QaLegacyMeetings::class, ['model' => $person])->set('name', 'Invalid')
        ->set('start_at', '2026-11-08 10:00')->set('finish_at', '2026-11-08 09:00')
        ->call('create')->assertHasErrors(['finish_at' => 'after_or_equal']);
    expect($person->meetings()->count())->toBe(0);
    $meeting = $person->meetings()->create(['name' => 'Original', 'start_at' => '2026-11-08 10:00', 'finish_at' => '2026-11-08 11:00']);
    Livewire::test(QaLegacyMeeting::class, ['meeting' => $meeting])
        ->set('start_at', '2026-11-08 10:00')->set('finish_at', '2026-11-08 09:00')
        ->call('update')->assertHasErrors(['finish_at' => 'after_or_equal']);
    expect($meeting->fresh()->finish_at->format('H:i'))->toBe('11:00');
});
