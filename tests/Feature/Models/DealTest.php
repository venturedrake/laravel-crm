<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Tests\TestCase;

class DealTest extends TestCase
{
    public function test_deal_uses_prefixed_table(): void
    {
        $this->assertSame('crm_deals', (new Deal)->getTable());
    }

    public function test_creating_a_deal_assigns_external_id_and_number(): void
    {
        app('laravel-crm.settings')->set('deal_prefix', 'D');

        $deal = Deal::create(['title' => 'Big Deal']);

        $this->assertTrue(Str::isUuid($deal->external_id));
        $this->assertSame(1000, $deal->number);
        $this->assertSame('D1000', $deal->deal_id);
    }

    public function test_set_amount_attribute_multiplies_by_one_hundred(): void
    {
        $deal = Deal::create(['title' => 'Money', 'amount' => 99.99]);

        $this->assertSame(9999, (int) $deal->fresh()->amount);
    }

    public function test_deal_increments_number_per_record(): void
    {
        $a = Deal::create(['title' => 'A']);
        $b = Deal::create(['title' => 'B']);

        $this->assertSame($a->number + 1, $b->number);
    }
}
