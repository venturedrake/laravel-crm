<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Services;

use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrm\Tests\TestCase;

class QuoteServiceTest extends TestCase
{
    private function service(): QuoteService
    {
        return app(QuoteService::class);
    }

    private function request(array $attributes): Request
    {
        return new Request($attributes);
    }

    public function test_service_creates_a_quote_with_minimum_data(): void
    {
        $quote = $this->service()->create($this->request([
            'title' => 'Quote 1',
            'description' => 'Some description',
            'currency' => 'USD',
            'sub_total' => 100,
            'tax' => 10,
            'total' => 110,
        ]));

        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertSame('Quote 1', $quote->title);
        $this->assertSame('USD', $quote->currency);
        $this->assertSame(10000, (int) $quote->fresh()->getRawOriginal('subtotal'));
        $this->assertSame(11000, (int) $quote->fresh()->getRawOriginal('total'));
    }

    public function test_service_attaches_person_and_organization(): void
    {
        $person = Person::create(['first_name' => 'Jane']);
        $org = Organization::create(['name' => 'Acme']);

        $quote = $this->service()->create($this->request([
            'title' => 'Linked',
            'currency' => 'USD',
        ]), $person, $org);

        $this->assertSame($person->id, $quote->person_id);
        $this->assertSame($org->id, $quote->organization_id);
    }

    public function test_service_updates_an_existing_quote(): void
    {
        $quote = Quote::create(['title' => 'Old', 'currency' => 'USD']);

        $this->service()->update($this->request([
            'title' => 'Updated',
            'description' => 'New desc',
            'currency' => 'AUD',
            'sub_total' => 50,
            'total' => 50,
        ]), $quote);

        $fresh = $quote->fresh();
        $this->assertSame('Updated', $fresh->title);
        $this->assertSame('New desc', $fresh->description);
        $this->assertSame('AUD', $fresh->currency);
    }
}

