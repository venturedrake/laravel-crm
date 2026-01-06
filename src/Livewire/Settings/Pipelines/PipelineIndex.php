<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Pipelines;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class PipelineIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'model', 'label' => ucfirst(__('laravel-crm::lang.attached_to')), 'format' => fn ($row, $field) => ucwords(\Illuminate\Support\Str::snake(class_basename($field), ' '))],
        ];
    }

    public function pipelines(): LengthAwarePaginator
    {
        return Pipeline::orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.pipelines.pipeline-index', [
            'headers' => $this->headers(),
            'pipelines' => $this->pipelines(),
        ]);
    }
}
