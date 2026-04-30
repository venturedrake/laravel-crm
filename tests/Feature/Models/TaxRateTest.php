<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Tests\TestCase;

class TaxRateTest extends TestCase
{
    public function test_tax_rate_uses_prefixed_table_name(): void
    {
        $this->assertSame('crm_tax_rates', (new TaxRate)->getTable());
    }

    public function test_tax_rate_persists_default_flag_and_rate(): void
    {
        $tax = TaxRate::create(['name' => 'GST', 'rate' => 10, 'default' => true]);

        $this->assertTrue((bool) $tax->fresh()->default);
        $this->assertEquals(10, $tax->fresh()->rate);
    }

    public function test_tax_rate_relationship_is_defined(): void
    {
        $this->assertInstanceOf(HasMany::class, (new TaxRate)->products());
    }

    public function test_tax_rate_uses_soft_deletes(): void
    {
        $tax = TaxRate::create(['name' => 'Bin', 'rate' => 0]);
        $tax->delete();
        $this->assertSoftDeleted('crm_tax_rates', ['id' => $tax->id]);
    }
}
