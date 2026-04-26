<?php

namespace VentureDrake\LaravelCrm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\QuoteProduct;

use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount;

class CheckAmountHelperTest extends TestCase
{
    public function test_line_amount_returns_true_when_price_times_quantity_matches_amount(): void
    {
        $item = new class extends \stdClass {};
        $item->price = 10;
        $item->quantity = 3;
        $item->amount = 30;

        // class_basename uses the class name; trick by aliasing
        $proxy = new class
        {
            public $price;

            public $quantity;

            public $amount;
        };
        $proxy->price = 10;
        $proxy->quantity = 3;
        $proxy->amount = 30;

        // The helper checks class_basename($item) for QuoteProduct/OrderProduct
        $quoteProduct = new QuoteProduct;
        $quoteProduct->price = 10;
        $quoteProduct->quantity = 3;
        $quoteProduct->amount = 30;

        $this->assertTrue(lineAmount($quoteProduct));
    }

    public function test_line_amount_returns_null_when_amounts_dont_match(): void
    {
        $orderProduct = new OrderProduct;
        $orderProduct->price = 10;
        $orderProduct->quantity = 3;
        $orderProduct->amount = 99;

        $this->assertNull(lineAmount($orderProduct));
    }

    public function test_line_amount_returns_null_for_unknown_class(): void
    {
        $obj = new \stdClass;
        $obj->price = 10;
        $obj->quantity = 3;
        $obj->amount = 30;

        $this->assertNull(lineAmount($obj));
    }
}
