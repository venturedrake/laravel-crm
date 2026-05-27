<?php

namespace VentureDrake\LaravelCrm\Livewire\Features;

use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureVoters extends Component
{
    use WithPagination;

    public Feature $feature;

    public function mount(Feature $feature): void
    {
        $this->feature = $feature;
    }

    public function render()
    {
        $rows = $this->feature->voters()
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        $headers = [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'email', 'label' => ucfirst(__('laravel-crm::lang.email'))],
            ['key' => 'voted_at', 'label' => ucfirst(__('laravel-crm::lang.voted_at'))],
        ];

        $mapped = $rows->through(fn ($user) => [
            'name' => $user->name,
            'email' => $user->email,
            'voted_at' => optional($user->pivot->created_at)->format('Y-m-d H:i'),
        ]);

        return view('laravel-crm::livewire.features.feature-voters', [
            'headers' => $headers,
            'rows' => $mapped,
        ]);
    }
}
