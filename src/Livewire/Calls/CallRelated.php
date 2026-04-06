<?php

namespace VentureDrake\LaravelCrm\Livewire\Calls;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Person;

class CallRelated extends Component
{
    use Toast;

    public $model = null;

    public $calls = [];

    public $name;

    public $description;

    public $start_at;

    public $finish_at;

    public array $guests;

    public $location;

    public $user_owner_id;

    public $user_assigned_id;

    public array $data = [];

    public array $revert = [];

    public function mount(): void
    {
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'start_at' => 'required',
            'finish_at' => 'required',
            'location' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ]);

        $call = $this->model->calls()->create([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'user_owner_id' => auth()->user()->id,
            'user_assigned_id' => auth()->user()->id,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $call->contacts()->create([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

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
    public function getCalls(): void
    {
        $callIds = [];
        $relatedIds = [];

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
            $this->data[$call->id] = array_merge([
                'editing' => false,
            ], $this->data[$call->id] ?? [], [
                'id' => $call->id,
                'name' => $call->name,
                'description' => $call->description,
                'start_at' => $call->start_at ? $call->start_at->format('Y-m-d\TH:i') : null,
                'finish_at' => $call->finish_at ? $call->finish_at->format('Y-m-d\TH:i') : null,
                'location' => $call->location,
                'user_owner_id' => $call->user_owner_id,
                'user_assigned_id' => $call->user_assigned_id,
                'related' => in_array($call->id, $relatedIds),
            ]);
        }
    }

    public function edit($id): void
    {
        $this->revert[$id] = $this->data[$id];
        $this->data[$id]['editing'] = true;
    }

    public function cancel($id): void
    {
        $this->data[$id]['editing'] = false;
        $this->data[$id] = $this->revert[$id];
    }

    public function update($id): void
    {
        $this->validate([
            'data.'.$id.'.name' => 'required|max:255',
            'data.'.$id.'.start_at' => 'required',
            'data.'.$id.'.finish_at' => 'required',
        ]);

        if ($call = $this->model->calls()->find($id)) {
            $call->update([
                'name' => $this->data[$id]['name'],
                'description' => $this->data[$id]['description'],
                'start_at' => $this->normalizeDatetime($this->data[$id]['start_at']),
                'finish_at' => $this->normalizeDatetime($this->data[$id]['finish_at']),
                'location' => $this->data[$id]['location'],
                'user_owner_id' => $this->data[$id]['user_owner_id'],
                'user_assigned_id' => $this->data[$id]['user_assigned_id'],
            ]);
        }

        $this->dispatch('call-updated');

        $this->success(
            ucfirst(trans('laravel-crm::lang.call_updated'))
        );

        $this->data[$id]['editing'] = false;
    }

    public function delete($id): void
    {
        if ($call = $this->model->calls()->find($id)) {
            $call->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.call_deleted')));
        }
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    private function resetFields(): void
    {
        $this->reset('name', 'description', 'start_at', 'finish_at', 'location');
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function render()
    {
        $this->getCalls();

        return view('laravel-crm::livewire.calls.call-related', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false),
            'persons' => Person::get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]);
    }
}
