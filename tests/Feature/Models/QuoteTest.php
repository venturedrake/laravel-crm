<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Tests\TestCase;

class QuoteTest extends TestCase
{
    public function test_quote_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_quotes', (new Quote)->getTable());

        config()->set('laravel-crm.db_table_prefix', 'foo_');
        $this->assertSame('foo_quotes', (new Quote)->getTable());
    }

    public function test_creating_a_quote_assigns_external_id_uuid(): void
    {
        $quote = Quote::create(['title' => 'Q']);

        $this->assertTrue(Str::isUuid($quote->external_id));
    }

    public function test_creating_a_quote_auto_increments_number_starting_from_1000(): void
    {
        $first = Quote::create(['title' => 'A']);
        $second = Quote::create(['title' => 'B']);

        $this->assertSame(1000, $first->number);
        $this->assertSame(1001, $second->number);
    }

    public function test_quote_id_is_built_from_prefix_plus_number(): void
    {
        app('laravel-crm.settings')->set('quote_prefix', 'Q');

        $quote = Quote::create(['title' => 'Prefixed']);

        $this->assertSame('Q', $quote->prefix);
        $this->assertSame('Q1000', $quote->quote_id);
    }

    public function test_set_money_attributes_multiply_by_one_hundred(): void
    {
        $quote = Quote::create([
            'title' => 'Money',
            'subtotal' => 100,
            'discount' => 10,
            'tax' => 9,
            'adjustments' => 1,
            'total' => 100,
        ]);

        $fresh = $quote->fresh();

        $this->assertSame(10000, (int) $fresh->getRawOriginal('subtotal'));
        $this->assertSame(1000, (int) $fresh->getRawOriginal('discount'));
        $this->assertSame(900, (int) $fresh->getRawOriginal('tax'));
        $this->assertSame(100, (int) $fresh->getRawOriginal('adjustments'));
        $this->assertSame(10000, (int) $fresh->getRawOriginal('total'));
    }

    public function test_money_attributes_handle_null(): void
    {
        $quote = Quote::create([
            'title' => 'Empty',
            'subtotal' => null,
            'total' => null,
        ]);

        $this->assertNull($quote->fresh()->getRawOriginal('subtotal'));
        $this->assertNull($quote->fresh()->getRawOriginal('total'));
    }

    public function test_quote_uses_soft_deletes(): void
    {
        $quote = Quote::create(['title' => 'Soft delete']);
        $quote->delete();

        $this->assertSoftDeleted('crm_quotes', ['id' => $quote->id]);
    }

    public function test_quote_relationships_are_defined(): void
    {
        $quote = new Quote;

        $this->assertInstanceOf(BelongsTo::class, $quote->person());
        $this->assertInstanceOf(BelongsTo::class, $quote->organization());
        $this->assertInstanceOf(BelongsTo::class, $quote->deal());
        $this->assertInstanceOf(BelongsTo::class, $quote->ownerUser());
        $this->assertInstanceOf(BelongsTo::class, $quote->pipeline());
        $this->assertInstanceOf(BelongsTo::class, $quote->pipelineStage());
        $this->assertInstanceOf(HasMany::class, $quote->quoteProducts());
        $this->assertInstanceOf(HasMany::class, $quote->orders());
        $this->assertInstanceOf(MorphToMany::class, $quote->labels());
    }

    public function test_quote_is_auditable(): void
    {
        $this->assertInstanceOf(Auditable::class, new Quote);
    }

    public function test_quote_belongs_to_person_and_organization(): void
    {
        $person = Person::create(['first_name' => 'Alice']);
        $org = Organization::create(['name' => 'Acme']);

        $quote = Quote::create([
            'title' => 'With links',
            'person_id' => $person->id,
            'organization_id' => $org->id,
        ]);

        $this->assertTrue($quote->person->is($person));
        $this->assertTrue($quote->organization->is($org));
    }
}
