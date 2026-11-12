<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Livewire\Traits\HasOrganizationSuggest;
use VentureDrake\LaravelCrm\Livewire\Traits\HasPersonSuggest;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Position;
use VentureDrake\LaravelCrm\Services\PositionService;

/**
 * The Positions panel on a person or an organisation page.
 *
 * On a person it lists the positions that person holds and has held, and the
 * form picks the organisation. On an organisation it lists who holds which
 * position there, and the form picks (or creates) the person.
 */
class RelatedPositions extends Component
{
    use AuthorizesRequests;
    use HasOrganizationSuggest;
    use HasPersonSuggest;
    use Toast;

    /** @var Person|Organization */
    public $model = null;

    public bool $showPositionForm = false;

    public bool $showPast = false;

    public string $search = '';

    public ?int $positionId = null;

    public $person_id;

    public $person_name;

    public $organization_id;

    public $organization_name;

    public $title;

    public $start_date;

    public $end_date;

    public $description;

    /**
     * Ids of positions the form would end that the user chose to keep open.
     *
     * @var array<int, int>
     */
    public array $keepOpen = [];

    public function isForPerson(): bool
    {
        return $this->model instanceof Person;
    }

    /**
     * Current positions that saving the form would end. Empty while editing:
     * only a new position replaces others.
     *
     * @return Collection<int, Position>
     */
    protected function conflicts(): Collection
    {
        if ($this->positionId || ! $this->showPositionForm) {
            return collect();
        }

        return app(PositionService::class)->conflicts(
            $this->person_id ? (int) $this->person_id : null,
            $this->organization_id ? (int) $this->organization_id : null,
            $this->title
        );
    }

    public function toggleKeepOpen($positionId): void
    {
        $positionId = (int) $positionId;

        $this->keepOpen = in_array($positionId, $this->keepOpen, true)
            ? array_values(array_diff($this->keepOpen, [$positionId]))
            : [...$this->keepOpen, $positionId];
    }

    /**
     * Open the form: on a position to edit it, or empty to add one.
     */
    public function openForm($positionId = null): void
    {
        $position = $positionId ? $this->model->positions()->find($positionId) : null;

        if ($positionId && ! $position) {
            return;
        }

        $this->resetForm();

        // A new position starts today, for the person or organisation the
        // panel is on; on a person it suggests their own organisation.
        $position ??= new Position([
            'start_date' => now()->toDateString(),
            'person_id' => $this->isForPerson() ? $this->model->id : null,
            'organization_id' => $this->isForPerson() ? $this->model->organization_id : $this->model->id,
        ]);

        $this->positionId = $position->id;
        $this->person_id = $position->person_id;
        $this->person_name = $position->person?->name;
        $this->organization_id = $position->organization_id;
        $this->organization_name = $position->organization?->name;
        $this->title = $position->title;
        $this->start_date = $position->start_date?->toDateString();
        $this->end_date = $position->end_date?->toDateString();
        $this->description = $position->description;

        $this->showPositionForm = true;
    }

    public function save(PositionService $positionService): void
    {
        $position = $this->positionId ? $this->model->positions()->findOrFail($this->positionId) : null;

        $position
            ? $this->authorize('update', $position)
            : $this->authorize('create', Position::class);

        $this->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:65535',
            'person_name' => $position || $this->isForPerson() ? 'nullable' : 'required|string|max:255',
            'organization_name' => $position || ! $this->isForPerson() ? 'nullable' : 'required|string|max:255',
        ], [], [
            'title' => __('laravel-crm::lang.position_title'),
            'start_date' => __('laravel-crm::lang.start_date'),
            'end_date' => __('laravel-crm::lang.end_date'),
            'person_name' => __('laravel-crm::lang.person'),
            'organization_name' => __('laravel-crm::lang.organization'),
        ]);

        $fields = [
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'description' => $this->description,
        ];

        $position
            ? $positionService->update($position, $fields)
            : $this->createPosition($positionService, $fields);

        $this->success(ucfirst(trans('laravel-crm::lang.'.($position ? 'position_updated' : 'position_added'))));

        $this->showPositionForm = false;
        $this->resetForm();
    }

    /**
     * @param  array{title:?string, start_date:?string, end_date:?string, description:?string}  $fields
     */
    protected function createPosition(PositionService $positionService, array $fields): void
    {
        // Work out what to end before a new person or organisation exists,
        // so the list matches what the form showed.
        $end = $this->conflicts()->pluck('id')->diff($this->keepOpen)->values()->all();

        [$person, $organization] = $this->resolveParties();

        $positionService->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
        ] + $fields, $end);

        // The new position may have linked the person and organisation;
        // refresh the Related organisations / Related people panels.
        $this->dispatch('related-contacts-updated');
    }

    /**
     * End a position today.
     */
    public function end($positionId, PositionService $positionService): void
    {
        if ($position = $this->model->positions()->find($positionId)) {
            $this->authorize('update', $position);

            $positionService->end($position);

            $this->success(ucfirst(trans('laravel-crm::lang.position_ended')));
        }
    }

    public function delete($positionId, PositionService $positionService): void
    {
        if ($position = $this->model->positions()->find($positionId)) {
            $this->authorize('delete', $position);

            $positionService->delete($position);

            $this->success(ucfirst(trans('laravel-crm::lang.position_deleted')));
        }
    }

    /**
     * The person and the organisation of a new position. The panel's own
     * record is one of them; the other is the one picked in the form, or is
     * created from the name typed there.
     *
     * @return array{0: Person, 1: Organization}
     */
    protected function resolveParties(): array
    {
        if ($this->isForPerson()) {
            $organization = $this->organization_id ? Organization::find($this->organization_id) : null;

            if (! $organization) {
                $this->authorize('create', Organization::class);

                $organization = Organization::create([
                    'name' => trim($this->organization_name),
                    'user_owner_id' => auth()->user()->id,
                ]);
            }

            return [$this->model, $organization];
        }

        $person = $this->person_id ? Person::find($this->person_id) : null;

        if (! $person) {
            $this->authorize('create', Person::class);

            $name = \VentureDrake\LaravelCrm\Http\Helpers\PersonName\firstLastFromName($this->person_name);

            $person = Person::create([
                'first_name' => $name['first_name'],
                'last_name' => $name['last_name'] ?? null,
                'organization_id' => $this->model->id,
                'user_owner_id' => auth()->user()->id,
            ]);
        }

        return [$person, $this->model];
    }

    protected function resetForm(): void
    {
        $this->reset(
            'positionId', 'person_id', 'person_name', 'organization_id', 'organization_name',
            'title', 'start_date', 'end_date', 'description', 'keepOpen'
        );
        $this->resetValidation();
        $this->showPeople = false;
        $this->showOrganizations = false;
    }

    public function render()
    {
        $needle = Str::lower(trim($this->search));

        // Current positions, plus past ones when asked for, narrowed by the
        // search box. Filtering happens in PHP because person and
        // organisation names may be stored encrypted.
        $positions = $this->model->positions()
            ->with(['person', 'organization'])
            ->when(! $this->showPast, fn ($query) => $query->current())
            ->get()
            ->when($needle !== '', fn (Collection $positions) => $positions->filter(
                fn (Position $position) => Str::contains(Str::lower($position->title), $needle)
                    || Str::contains(Str::lower((string) $position->person?->name), $needle)
                    || Str::contains(Str::lower((string) $position->organization?->name), $needle)
            ))
            ->sortBy(fn (Position $position) => $position->isCurrent() ? 0 : 1)
            ->values();

        return view('laravel-crm::livewire.related-positions', [
            'positions' => $positions,
            'conflicts' => $this->conflicts(),
            'titleSuggestions' => $this->showPositionForm ? app(PositionService::class)->titleSuggestions() : collect(),
            'dateFormat' => app('laravel-crm.settings')->get('date_format', 'Y-m-d'),
        ]);
    }
}
