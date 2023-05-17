<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\CheckAmount;

function subTotal($model)
{
    return false;
    
    foreach(\VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\getItems($model) as $item){
        // 
    }
    
    if($model->subtotal == 0){
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