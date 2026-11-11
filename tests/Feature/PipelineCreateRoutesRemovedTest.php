<?php

/*
 * The v1 pipelines and pipeline-stages `create` / `store` routes were left registered
 * in v2. Their controller methods returned views v2 never added, so opening
 * /crm/pipelines/create or /crm/pipeline-stages/create threw an
 * InvalidArgumentException and returned a 500 (#107). `store` was also broken: it
 * created a pipeline without the `model` column, so the pipeline wasn't tied to
 * anything.
 *
 * v2 pipelines are a fixed seeded set, one per model, so their create/store path
 * stays removed and /crm/pipelines/create still falls through to the {pipeline}
 * show route, finds no record and returns a 404.
 *
 * Pipeline stages are different: stages can be edited and deleted but, until #114,
 * could not be recreated anywhere, so a stage deleted by mistake was gone for good.
 * The stage create page is back as a v2 Livewire component with its own GET route;
 * the form saves through the component, so no v1-style store route is needed.
 */

use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Controllers\PipelineController;
use VentureDrake\LaravelCrm\Http\Controllers\PipelineStageController;

test('no pipeline create or store route, and the stage create page exists without a store route', function () {
    expect(Route::has('laravel-crm.pipelines.create'))->toBeFalse()
        ->and(Route::has('laravel-crm.pipelines.store'))->toBeFalse()
        ->and(Route::has('laravel-crm.pipeline-stages.create'))->toBeTrue()
        ->and(Route::has('laravel-crm.pipeline-stages.store'))->toBeFalse();
});

test('the pipeline controllers keep no v1 create or store path', function () {
    expect(method_exists(PipelineController::class, 'create'))->toBeFalse()
        ->and(method_exists(PipelineController::class, 'store'))->toBeFalse()
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

test('the old pipeline create url returns 404 and the pipeline stage create page loads', function () {
    $this->actingAsUserWithPermissions([
        'view crm pipelines',
        'create crm pipelines',
        'edit crm pipelines',
        'delete crm pipelines',
    ]);

    $this->withoutVite();

    $this->get('/'.config('laravel-crm.route_prefix').'/pipelines/create')->assertNotFound();
    $this->get('/'.config('laravel-crm.route_prefix').'/pipeline-stages/create')->assertOk();
});

test('the pipeline stage create page is closed to users who cannot create stages', function () {
    $this->actingAsUserWithPermissions([
        'view crm pipelines',
    ]);

    $this->withoutVite();

    $this->get('/'.config('laravel-crm.route_prefix').'/pipeline-stages/create')->assertForbidden();
});
