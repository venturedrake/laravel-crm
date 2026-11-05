<?php

namespace VentureDrake\LaravelCrm\Livewire\Meetings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Person;

class MeetingItem extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

    public Meeting $meeting;

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

    public function mount(Meeting $meeting, bool $related = false): void
    {
        $this->meeting = $meeting;
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
        $this->name = $this->meeting->name ?? '';
        $this->description = $this->meeting->description;
        $this->start_at = $this->meeting->start_at?->format('Y-m-d\TH:i');
        $this->finish_at = $this->meeting->finish_at?->format('Y-m-d\TH:i');
        $this->location = $this->meeting->location;
        $this->user_owner_id = $this->meeting->user_owner_id;
        $this->user_assigned_id = $this->meeting->user_assigned_id;
        $this->guests = $this->meeting->contacts()->pluck('entityable_id')->toArray();
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
        $this->authorize('update', $this->meeting);

        $this->validate([
            'name' => 'required|max:255',
            'start_at' => 'required',
            'finish_at' => 'required',
        ]);

        $this->meeting->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->meeting->contacts()->whereNotIn('entityable_id', $this->guests)->delete();

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->meeting->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->dispatch('meeting-updated');
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.meeting_updated')));

        $this->editing = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->meeting);

        $this->meeting->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.meeting_deleted')));

        $this->dispatch('meeting-updated');
        $this->dispatch('activity-logged');
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('update', $this->meeting);
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    public function render()
    {
        return view('laravel-crm::livewire.meetings.meeting-item');
    }
}
