<?php

namespace VentureDrake\LaravelCrm\Livewire\Lunches;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Person;

class LunchRelated extends Component
{
    use Toast;

    public $model = null;

    public $lunches = [];

    public $name;

    public $description;

    public $start_at;

    public $finish_at;

    public array $guests = [];

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
            'guests' => 'nullable',
            'location' => 'nullable',
            'user_owner_id' => 'nullable',
            'user_assigned_id' => 'nullable',
        ]);

        $lunch = $this->model->lunches()->create([
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->normalizeDatetime($this->start_at),
            'finish_at' => $this->normalizeDatetime($this->finish_at),
            'location' => $this->location,
            'user_owner_id' => $this->user_owner_id,
            'user_assigned_id' => $this->user_assigned_id,
        ]);

        foreach ($this->guests as $personId) {
            if ($person = Person::find($personId)) {
                $lunch->contacts()->create([
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
            'recordable_type' => $lunch->getMorphClass(),
            'recordable_id' => $lunch->id,
        ]);

        $this->dispatch('lunch-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.lunch_created'))
        );

        $this->resetFields();
    }

    #[On('lunch-updated')]
    public function getLunches(): void
    {
        $lunchIds = [];
        $relatedIds = [];

        foreach ($this->model->lunches()->latest()->get() as $lunch) {
            $lunchIds[] = $lunch->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
            if (method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->lunches()->latest()->get() as $lunch) {
                        $lunchIds[] = $lunch->id;
                        $relatedIds[] = $lunch->id;
                    }
                }
            }

            if (method_exists($this->model, 'organization') && $this->model->organization) {
                foreach ($this->model->organization->lunches()->latest()->get() as $lunch) {
                    $lunchIds[] = $lunch->id;
                    $relatedIds[] = $lunch->id;
                }
            }

            if (method_exists($this->model, 'person') && $this->model->person) {
                foreach ($this->model->person->lunches()->latest()->get() as $lunch) {
                    $lunchIds[] = $lunch->id;
                    $relatedIds[] = $lunch->id;
                }
            }
        }

        if (count($lunchIds) > 0) {
            $this->lunches = Lunch::whereIn('id', $lunchIds)->latest()->get();
        } else {
            $this->lunches = collect();
        }

        foreach ($this->lunches as $lunch) {
            $this->data[$lunch->id] = array_merge([
                'editing' => false,
            ], $this->data[$lunch->id] ?? [], [
                'id' => $lunch->id,
                'name' => $lunch->name,
                'description' => $lunch->description,
                'start_at' => $lunch->start_at ? $lunch->start_at->format('Y-m-d\TH:i') : null,
                'finish_at' => $lunch->finish_at ? $lunch->finish_at->format('Y-m-d\TH:i') : null,
                'location' => $lunch->location,
                'user_owner_id' => $lunch->user_owner_id,
                'user_assigned_id' => $lunch->user_assigned_id,
                'related' => in_array($lunch->id, $relatedIds),
                'guests' => $lunch->contacts->pluck('entityable_id')->toArray(),
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

        if ($lunch = $this->model->lunches()->find($id)) {
            $lunch->update([
                'name' => $this->data[$id]['name'],
                'description' => $this->data[$id]['description'],
                'start_at' => $this->normalizeDatetime($this->data[$id]['start_at']),
                'finish_at' => $this->normalizeDatetime($this->data[$id]['finish_at']),
                'location' => $this->data[$id]['location'],
                'user_owner_id' => $this->data[$id]['user_owner_id'],
                'user_assigned_id' => $this->data[$id]['user_assigned_id'],
            ]);

            $newGuestIds = $this->data[$id]['guests'] ?? [];

            $lunch->contacts()->whereNotIn('entityable_id', $newGuestIds)->delete();

            foreach ($newGuestIds as $personId) {
                if ($person = Person::find($personId)) {
                    $lunch->contacts()->firstOrCreate([
                        'entityable_type' => $person->getMorphClass(),
                        'entityable_id' => $person->id,
                    ]);
                }
            }
        }

        $this->dispatch('lunch-updated');

        $this->success(
            ucfirst(trans('laravel-crm::lang.lunch_updated'))
        );

        $this->data[$id]['editing'] = false;
    }

    public function delete($id): void
    {
        if ($lunch = $this->model->lunches()->find($id)) {
            $lunch->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.lunch_deleted')));
        }
    }

    private function normalizeDatetime(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    private function resetFields(): void
    {
        $this->reset('name', 'description', 'start_at', 'finish_at', 'location', 'guests');
        $this->user_owner_id = auth()->user()->id;
        $this->user_assigned_id = auth()->user()->id;
    }

    public function render()
    {
        $this->getLunches();

        return view('laravel-crm::livewire.lunches.lunch-related', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false),
            'persons' => Person::get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]);
    }
}
