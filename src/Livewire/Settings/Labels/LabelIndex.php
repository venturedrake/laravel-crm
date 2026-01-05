<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Labels;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class LabelIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'color', 'label' => ucfirst(__('laravel-crm::lang.color'))],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
            ['key' => 'updated_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
        ];
    }

    public function labels(): LengthAwarePaginator
    {
        return Label::orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($label = Label::find($id)) {
            $label->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.label_deleted')));
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.labels.label-index', [
            'headers' => $this->headers(),
            'labels' => $this->labels(),
        ]);
    }
}
