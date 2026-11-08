<?php

/*
 * The v1 pipelines and pipeline-stages `create` / `store` routes were left registered
 * in v2. Their controller methods returned views v2 never added, so opening
 * /crm/pipelines/create or /crm/pipeline-stages/create threw an
 * InvalidArgumentException and returned a 500 (#107). `store` was also broken: it
 * created a pipeline without the `model` column, so the pipeline wasn't tied to
 * anything.
 *
 * v2 pipelines are a fixed seeded set, one per model, and stages are edited through
 * Livewire, so the create/store path was removed instead of being rebuilt. This file
 * keeps it removed. /crm/pipelines/create now falls through to the {pipeline} show
 * route, finds no record and returns a 404.
 */

use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Controllers\PipelineController;
use VentureDrake\LaravelCrm\Http\Controllers\PipelineStageController;

test('no pipeline or pipeline stage create/store route is registered', function () {
    expect(Route::has('laravel-crm.pipelines.create'))->toBeFalse()
        ->and(Route::has('laravel-crm.pipelines.store'))->toBeFalse()
        ->and(Route::has('laravel-crm.pipeline-stages.create'))->toBeFalse()
        ->and(Route::has('laravel-crm.pipeline-stages.store'))->toBeFalse();
});

test('the pipeline controllers have no create or store method and the store requests are gone', function () {
    expect(method_exists(PipelineController::class, 'create'))->toBeFalse()
        ->and(method_exists(PipelineController::class, 'store'))->toBeFalse()
        ->and(method_exists(PipelineStageController::class, 'create'))->toBeFalse()
        ->and(method_exists(PipelineStageController::class, 'store'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Http\Requests\StorePipelineRequest'))->toBeFalse()
        ->and(class_exists('VentureDrake\LaravelCrm\Http\Requests\StorePipelineStageRequest'))->toBeFalse();
});

test('the remaining pipeline and pipeline stage routes are still registered', function () {
    foreach (['index', 'show', 'edit', 'update', 'destroy'] as $action) {
        expect(Route::has("laravel-crm.pipelines.{$action}"))->toBeTrue()
            ->and(Route::has("laravel-crm.pipeline-stages.{$action}"))->toBeTrue();
    }
});

test('opening the old create urls returns 404 instead of 500', function (string $path) {
    $this->actingAsUserWithPermissions([
        'view crm pipelines',
        'create crm pipelines',
        'edit crm pipelines',
        'delete crm pipelines',
    ]);

    $this->get('/'.config('laravel-crm.route_prefix').$path)->assertNotFound();
})->with([
    'pipelines' => '/pipelines/create',
    'pipeline stages' => '/pipeline-stages/create',
]);
