<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

class ActivityTabs extends Component
{
    public $model;

    public $activeTab = 'activity';

    #[On('select-activity-tab')]
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Whether to show the tab listing the model's records of this relation.
     *
     * People and organizations have orders(), invoices() and purchaseOrders()
     * only so the delete dialog can count what still points at them; they do
     * not get those tabs.
     */
    public function hasRelatedTab(string $relation): bool
    {
        if ($this->model instanceof Person || $this->model instanceof Organization) {
            return false;
        }

        return method_exists($this->model, $relation);
    }

    public function render()
    {
        return view('laravel-crm::livewire.activity-tabs');
    }
}
