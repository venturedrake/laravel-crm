<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Helpers;

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Tests\TestCase;

use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\dateFormats;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\fieldModels;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes;
use function VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\timeFormats;

class SelectOptionsHelperTest extends TestCase
{
    public function test_options_from_model_returns_id_keyed_array(): void
    {
        $items = [
            (object) ['id' => 1, 'name' => 'A'],
            (object) ['id' => 2, 'name' => 'B'],
        ];

        $result = optionsFromModel($items);

        $this->assertSame(['' => '', 1 => 'A', 2 => 'B'], $result);
    }

    public function test_options_from_model_can_skip_null_entry(): void
    {
        $result = optionsFromModel([(object) ['id' => 1, 'name' => 'A']], false);

        $this->assertArrayNotHasKey('', $result);
        $this->assertSame('A', $result[1]);
    }

    public function test_phone_types_returns_expected_options(): void
    {
        $options = phoneTypes(false);
        $ids = array_column($options, 'id');

        $this->assertContains('work', $ids);
        $this->assertContains('home', $ids);
        $this->assertContains('mobile', $ids);
        $this->assertContains('fax', $ids);
        $this->assertContains('other', $ids);
    }

    public function test_email_types_returns_expected_options(): void
    {
        $options = emailTypes(false);
        $ids = array_column($options, 'id');

        $this->assertContains('work', $ids);
        $this->assertContains('home', $ids);
        $this->assertContains('other', $ids);
        $this->assertNotContains('mobile', $ids);
    }

    public function test_date_formats_includes_common_formats(): void
    {
        $formats = dateFormats();
        $this->assertArrayHasKey('Y-m-d', $formats);
        $this->assertArrayHasKey('d/m/Y', $formats);
        $this->assertArrayHasKey('m/d/Y', $formats);
    }

    public function test_time_formats_includes_common_formats(): void
    {
        $formats = timeFormats();
        $this->assertArrayHasKey('g:i a', $formats);
        $this->assertArrayHasKey('H:i', $formats);
    }

    public function test_field_models_lists_supported_entities(): void
    {
        $fields = fieldModels();

        $this->assertArrayHasKey(Lead::class, $fields);
        $this->assertArrayHasKey(Deal::class, $fields);
        $this->assertArrayHasKey(Person::class, $fields);
        $this->assertArrayHasKey(Organization::class, $fields);
        $this->assertArrayHasKey(Product::class, $fields);
    }
}
