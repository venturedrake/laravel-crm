<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\PublicProperties;

use Illuminate\Http\Request;

function publicProperties($object): array
{
    if ($object instanceof \stdClass) {
        return (array) $object;
    }

    $reflection = new \ReflectionClass($object);
    $properties = [];

    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
        $properties[$property->getName()] = $property->getValue($object);
    }

    return $properties;
}

function asRequest($object)
{
    // Convert public property name to request input format
    $request = new Request;
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
