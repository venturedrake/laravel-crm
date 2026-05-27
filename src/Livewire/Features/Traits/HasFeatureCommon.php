<?php

namespace VentureDrake\LaravelCrm\Livewire\Features\Traits;

use Illuminate\Support\Collection;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Services\FeatureService;

trait HasFeatureCommon
{
    use Toast;

    protected FeatureService $featureService;

    public ?string $title = null;

    public ?string $description = null;

    public bool $is_public = true;

    public $feature_status_id = null;

    public function boot(FeatureService $featureService): void
    {
        $this->featureService = $featureService;
    }

    public function statusOptions(): Collection
    {
        return FeatureStatus::orderBy('order')->orderBy('id')->get();
    }

    protected function rules()
    {
        return [
            'title' => 'required|max:255',
            'description' => 'nullable|max:5000',
            'is_public' => 'boolean',
            'feature_status_id' => 'nullable|exists:'.config('laravel-crm.db_table_prefix').'feature_statuses,id',
        ];
    }
}
