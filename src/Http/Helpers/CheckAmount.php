<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\CheckAmount;

function subTotal($model)
{
    $total = 0;

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item) {
        $total += $item->quantity * $item->price;
    }

    if ($model->subtotal == $total) {
        return true;
    }
}

function total($model)
{
    $total = 0;

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item) {
        $total += $item->quantity * $item->price;
    }

    $total = $total - $model->discount + $model->tax + $model->adjustments;

    if ($model->total == $total) {
        return true;
    }
}

function lineAmount($item)
{
    switch (class_basename($item)) {
        case "QuoteProduct":
        case "OrderProduct":
            if (($item->price * $item->quantity) == $item->amount) {
                return true;
            }

            break;
    }
}

function getItems($model)
{
    switch (class_basename($model)) {
        case "Quote":
            return $model->quoteProducts()->whereNotNull('product_id')->get();

            break;

        case "Order":
            return $model->orderProducts()->whereNotNull('product_id')->get();

            break;
    }
}
