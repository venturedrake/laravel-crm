<?php

namespace VentureDrake\LaravelCrm\Livewire\Calls;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Person;

class CallItem extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

    public Call $call;

    public bool $related = false;

    public bool $editing = false;

    /**
     * Whether the item is opened to its full details. Bound to the collapse
     * widget, so it survives the list around the item being refreshed.
     */
    public bool $expanded = false;

    public string $name = '';

    public ?string $description = null;

    public ?string $start_at = null;

    public ?string $finish_at = null;

    public array $guests = [];

    public ?string $location = null;

    public ?int $user_owner_id = null;

    public ?int $user_assigned_id = null;

    public function mount(Call $call, bool $related = false): void
    {
        $this->call = $call;
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
        $this->name = $this->call->name ?? '';
        $this->description = $this->call->description;
        $this->start_at = $this->call->start_at?->format('Y-m-d\TH:i');
        $this->finish_at = $this->call->finish_at?->format('Y-m-d\TH:i');
        $this->location = $this->call->location;
        $this->user_owner_id = $this->call->user_owner_id;
        $this->user_assigned_id = $this->call->user_assigned_id;
        $this->guests = $this->call->contacts()->pluck('entityable_id')->toArray();
    }

    public function edit(): void
    {
        $this->loadSelectedGuests();

        $this->editing = true;
        $this->expanded = true;
    }

    public function cancel(): void
    {
        $this->hydrateFromRecord();
        $this->editing = false;
    }

    /**
     * Another copy of this call on the page was changed (the same call is
     * shown in its own tab and on the timeline): show what was saved. The
     * record itself is re-read on every request; only the form fields need
     * filling again, and not while they are being edited here.
     */
    #[On('call-updated.{call.id}')]
    public function refreshFromRecord(): void
    {
        if (! $this->editing) {
            $this->hydrateFromRecord();
        }
    }

    public function update(): void
    {
        $this->authorize('update', $this->call);

        $this->validate([
            'name' => 'required|max:255',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
        ]);

        $this->call->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        $this->call->contacts()->whereNotIn('entityable_id', $this->guests)->delete();

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $this->call->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->dispatch('call-updated');
        $this->dispatch('call-updated.'.$this->call->id);
        $this->dispatch('activity-logged');

        $this->success(ucfirst(trans('laravel-crm::lang.call_updated')));

        $this->editing = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->call);

        $this->call->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.call_deleted')));

        $this->dispatch('call-updated');
        $this->dispatch('activity-logged');
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('update', $this->call);
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    public function render()
    {
        return view('laravel-crm::livewire.calls.call-item');
    }
}
