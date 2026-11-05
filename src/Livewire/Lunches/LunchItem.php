<?php

namespace VentureDrake\LaravelCrm\Livewire\Lunches;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Person;

class LunchItem extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

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

    public function mount(Lunch $lunch, bool $related = false): void
    {
        $this->lunch = $lunch;
        $this->related = $related;

        $this->hydrateFromRecord();
    }

    /**
     * Fill the form fields from the stored record.
     *
     * Cancelling restores from the record rather than from a snapshot taken in edit():
     * edit() and cancel() are separate requests, so a snapshot would have to survive the
     * round trip on a public property. Reading the record back is both cheaper and
     * correct when someone else has edited it in the meantime.
     */
    private function hydrateFromRecord(): void
    {
        $this->name = $this->lunch->name ?? '';
        $this->description = $this->lunch->description;
        $this->start_at = $this->lunch->start_at?->format('Y-m-d\TH:i');
        $this->finish_at = $this->lunch->finish_at?->format('Y-m-d\TH:i');
        $this->location = $this->lunch->location;
        $this->user_owner_id = $this->lunch->user_owner_id;
        $this->user_assigned_id = $this->lunch->user_assigned_id;
        $this->guests = $this->lunch->contacts()->pluck('entityable_id')->toArray();
    }

    public function edit(): void
    {
        $this->loadSelectedGuests();

        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->hydrateFromRecord();
        $this->editing = false;
    }

    public function update(): void
    {
        $this->authorize('update', $this->lunch);

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
        $this->authorize('delete', $this->lunch);

        $this->lunch->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.lunch_deleted')));

        $this->dispatch('lunch-updated');
        $this->dispatch('activity-logged');
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('update', $this->lunch);
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    public function render()
    {
        return view('laravel-crm::livewire.lunches.lunch-item');
    }
}
