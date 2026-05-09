<?php

use VentureDrake\LaravelCrm\Tests\TestCase;
use VentureDrake\LaravelCrm\Tests\V1TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(V1TestCase::class)->in('Upgrade');
