<?php

namespace VentureDrake\LaravelCrm\Livewire\Lunches;

use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Person;

class LunchItem extends Component
{
    use Toast;

    public Lunch $lunch;

    public bool $related = false;

    public bool $editing = false;

    public string $name = '';

    public ?string $description = null;

    public ?string $start_at = null;

    public ?string $finish_at = null;

    public array $guests = [];

    public ?string $location = null;

    public ?int $user_owner_id = null;

    public ?int $user_assigned_id = null;

    private array $revert = [];

    public function mount(Lunch $lunch, bool $related = false): void
    {
        $this->lunch = $lunch;
        $this->related = $related;
        $this->name = $lunch->name ?? '';
        $this->description = $lunch->description;
        $this->start_at = $lunch->start_at?->format('Y-m-d\TH:i');
        $this->finish_at = $lunch->finish_at?->format('Y-m-d\TH:i');
        $this->location = $lunch->location;
        $this->user_owner_id = $lunch->user_owner_id;
        $this->user_assigned_id = $lunch->user_assigned_id;
        $this->guests = $lunch->contacts->pluck('entityable_id')->toArray();
    }

    public function edit(): void
    {
        $this->revert = [
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'location' => $this->location,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
            'guests' => $this->guests,
        ];

        $this->editing = true;
    }

    public function cancel(): void
    {
        foreach ($this->revert as $key => $value) {
            $this->$key = $value;
        }
        $this->editing = false;
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'start_at' => 'required',
            'finish_at' => 'required',
        ]);

        $this->lunch->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->lunch->contacts()->whereNotIn('entityable_id', $this->guests)->delete();

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->lunch->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->dispatch('lunch-updated');
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.lunch_updated')));

        $this->editing = false;
    }

    public function delete(): void
    {
        $this->lunch->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.lunch_deleted')));

        $this->dispatch('lunch-updated');
        $this->dispatch('activity-logged');
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    public function render()
    {
        return view('laravel-crm::livewire.lunches.lunch-item', [
            'persons' => Person::get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]);
    }
}
