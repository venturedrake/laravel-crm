<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\PublicProperties;

function asRequest($object)
{
    // Convert public property name to request input format
    $request = new \Illuminate\Http\Request;
    $reflection = new \ReflectionClass($object);

    $publicAttributes = [];

    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
        $publicAttributes[$property->getName()] = $property->getValue($object);
    }

    return $request->replace($publicAttributes);
}

function asArray($object)
{
    // Convert public property name to an array
    $reflection = new \ReflectionClass($object);

    $publicAttributes = [];

    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
        $publicAttributes[$property->getName()] = $property->getValue($object);
    }

    return $publicAttributes;
}
