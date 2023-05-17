<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\CheckAmount;

function subTotal($model)
{
    $total = 0;
    
    foreach(\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item){
        $total += $item->quantity * $item->price;
    }
    
    if($model->subtotal == $total){
        return true;
    }
}

function getItems($model)
{
    switch(class_basename($model)){
        case "Quote":
            return $model->quoteProducts()->whereNotNull('product_id')->get();
            break;
    }
}