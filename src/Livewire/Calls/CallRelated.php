<?php

namespace VentureDrake\LaravelCrm\Livewire\Calls;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasGuestSearch;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Person;

class CallRelated extends Component
{
    use AuthorizesRequests, HasGuestSearch, Toast;

    public $model = null;

    public $calls = [];

    public $name;

    public $description;

    public $start_at;

    public $finish_at;

    public string $direction = Call::OUTBOUND;

    public array $guests = [];

    /**
     * Ids of the CRM users who were on the call.
     */
    public array $team_members = [];

    public $location;

    public $user_owner_id;

    public $user_assigned_id;

    public array $data = [];

    public function mount(): void
    {
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;

        $this->prefillParticipants();
    }

    /**
     * A new call starts with the person it is about as a guest, and with the
     * user logging it as the team member on it. Either can be removed, so a
     * call can be logged for a colleague.
     */
    private function prefillParticipants(): void
    {
        $person = $this->model instanceof Person
            ? $this->model
            : (is_object($this->model) && method_exists($this->model, 'person') ? $this->model->person : null);

        $this->guests = $person ? [$person->id] : [];
        $this->team_members = [auth()->user()->id];

        $this->loadSelectedGuests();
    }

    public function save(): void
    {
        $this->authorize('create', Call::class);

        $this->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'location' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
            'team_members' => 'array',
            'direction' => 'required|in:'.Call::OUTBOUND.','.Call::INBOUND,
        ]);

        $call = $this->model->calls()->create([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'direction' => $this->direction,
            'user_owner_id' => auth()->user()->id,
            'user_assigned_id' => auth()->user()->id,
        ]);

        $call->syncGuests($this->guests);
        $call->syncTeamMembers($this->team_members);

        $this->model->activities()->create([
            'causeable_type' => auth()->user()->getMorphClass(),
            'causeable_id' => auth()->user()->id,
            'timelineable_type' => $this->model->getMorphClass(),
            'timelineable_id' => $this->model->id,
            'recordable_type' => $call->getMorphClass(),
            'recordable_id' => $call->id,
        ]);

        $this->dispatch('call-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.call_created'))
        );

        $this->resetFields();
    }

    #[On('call-updated')]
    #[On('call-added')]
    public function getCalls(): void
    {
        $relatedIds = [];
        $callIds = [];

        foreach ($this->model->calls()->latest()->get() as $call) {
            $callIds[] = $call->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
            if (method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->calls()->latest()->get() as $call) {
                        $callIds[] = $call->id;
                        $relatedIds[] = $call->id;
                    }
                }
            }

            if (method_exists($this->model, 'organization') && $this->model->organization) {
                foreach ($this->model->organization->calls()->latest()->get() as $call) {
                    $callIds[] = $call->id;
                    $relatedIds[] = $call->id;
                }
            }

            if (method_exists($this->model, 'person') && $this->model->person) {
                foreach ($this->model->person->calls()->latest()->get() as $call) {
                    $callIds[] = $call->id;
                    $relatedIds[] = $call->id;
                }
            }
        }

        if (count($callIds) > 0) {
            $this->calls = Call::whereIn('id', $callIds)->latest()->get();
        } else {
            $this->calls = collect();
        }

        foreach ($this->calls as $call) {
            $this->data[$call->id] = [
                'related' => in_array($call->id, $relatedIds),
            ];
        }
    }

    protected function authorizeGuestSearch(): void
    {
        $this->authorize('create', Call::class);
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    private function resetFields(): void
    {
        $this->reset('name', 'description', 'start_at', 'finish_at', 'location', 'direction', 'guests', 'guestOptions');
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;

        $this->prefillParticipants();
    }

    public function render()
    {
        $this->getCalls();

        return view('laravel-crm::livewire.calls.call-related', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false, false),
        ]);
    }
}
