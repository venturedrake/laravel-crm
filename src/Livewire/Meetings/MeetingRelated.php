<?php

namespace VentureDrake\LaravelCrm\Livewire\Meetings;

use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Person;

class MeetingRelated extends Component
{
    use Toast;

    public $model = null;

    public $meetings = [];

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

        $meeting = $this->model->meetings()->create([
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
                $meeting->contacts()->create([
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
            'recordable_type' => $meeting->getMorphClass(),
            'recordable_id' => $meeting->id,
        ]);

        $this->dispatch('meeting-added');
        $this->dispatch('activity-logged');

        $this->success(
            ucfirst(trans('laravel-crm::lang.meeting_created'))
        );

        $this->resetFields();
    }

    #[On('meeting-updated')]
    public function getMeetings(): void
    {
        $meetingIds = [];
        $relatedIds = [];

        foreach ($this->model->meetings()->latest()->get() as $meeting) {
            $meetingIds[] = $meeting->id;
        }

        if (app('laravel-crm.settings')->get('show_related_activity') == 1) {
            if (method_exists($this->model, 'contacts')) {
                foreach ($this->model->contacts as $contact) {
                    foreach ($contact->entityable->meetings()->latest()->get() as $meeting) {
                        $meetingIds[] = $meeting->id;
                        $relatedIds[] = $meeting->id;
                    }
                }
            }

            if (method_exists($this->model, 'organization') && $this->model->organization) {
                foreach ($this->model->organization->meetings()->latest()->get() as $meeting) {
                    $meetingIds[] = $meeting->id;
                    $relatedIds[] = $meeting->id;
                }
            }

            if (method_exists($this->model, 'person') && $this->model->person) {
                foreach ($this->model->person->meetings()->latest()->get() as $meeting) {
                    $meetingIds[] = $meeting->id;
                    $relatedIds[] = $meeting->id;
                }
            }
        }

        if (count($meetingIds) > 0) {
            $this->meetings = Meeting::whereIn('id', $meetingIds)->latest()->get();
        } else {
            $this->meetings = collect();
        }

        foreach ($this->meetings as $meeting) {
            $this->data[$meeting->id] = array_merge([
                'editing' => false,
            ], $this->data[$meeting->id] ?? [], [
                'id' => $meeting->id,
                'name' => $meeting->name,
                'description' => $meeting->description,
                'start_at' => $meeting->start_at ? $meeting->start_at->format('Y-m-d\TH:i') : null,
                'finish_at' => $meeting->finish_at ? $meeting->finish_at->format('Y-m-d\TH:i') : null,
                'location' => $meeting->location,
                'user_owner_id' => $meeting->user_owner_id,
                'user_assigned_id' => $meeting->user_assigned_id,
                'related' => in_array($meeting->id, $relatedIds),
                'guests' => $meeting->contacts->pluck('entityable_id')->toArray(),
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

        if ($meeting = $this->model->meetings()->find($id)) {
            $meeting->update([
                'name' => $this->data[$id]['name'],
                'description' => $this->data[$id]['description'],
                'start_at' => $this->normalizeDatetime($this->data[$id]['start_at']),
                'finish_at' => $this->normalizeDatetime($this->data[$id]['finish_at']),
                'location' => $this->data[$id]['location'],
                'user_owner_id' => $this->data[$id]['user_owner_id'],
                'user_assigned_id' => $this->data[$id]['user_assigned_id'],
            ]);

            $newGuestIds = $this->data[$id]['guests'] ?? [];

            $meeting->contacts()->whereNotIn('entityable_id', $newGuestIds)->delete();

            foreach ($newGuestIds as $personId) {
                if ($person = Person::find($personId)) {
                    $meeting->contacts()->firstOrCreate([
                        'entityable_type' => $person->getMorphClass(),
                        'entityable_id' => $person->id,
                    ]);
                }
            }
        }

        $this->dispatch('meeting-updated');

        $this->success(
            ucfirst(trans('laravel-crm::lang.meeting_updated'))
        );

        $this->data[$id]['editing'] = false;
    }

    public function delete($id): void
    {
        if ($meeting = $this->model->meetings()->find($id)) {
            $meeting->delete();

            $this->success(ucfirst(trans('laravel-crm::lang.meeting_deleted')));
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
        $this->getMeetings();

        return view('laravel-crm::livewire.meetings.meeting-related', [
            'users' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\usersOptions(false),
            'persons' => Person::get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]);
    }
}
