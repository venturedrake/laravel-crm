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
    #[On('lunch-added')]
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
            $this->data[$lunch->id] = [
                'related' => in_array($lunch->id, $relatedIds),
            ];
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
