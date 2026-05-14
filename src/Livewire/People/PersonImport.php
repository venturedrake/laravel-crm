<?php

namespace VentureDrake\LaravelCrm\Livewire\People;

use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Component;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

class PersonImport extends Component
{
    use Toast;

    public bool $hasPreview = false;

    public bool $processing = false;

    public bool $imported = false;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public int $pendingOffset = 0;

    public int $totalToProcess = 0;

    public int $page = 1;

    protected const PER_PAGE = 50;

    protected const CHUNK_SIZE = 25;

    protected const SESSION_KEY = 'crm_person_import_preview';

    /** Cache of organizations keyed by lowercased name for lookups during import. */
    private ?array $organizationCache = null;

    public function mount(): void
    {
        $this->hasPreview = session()->has(self::SESSION_KEY);
    }

    public function startImport(): void
    {
        $rows = session(self::SESSION_KEY, []);

        if (empty($rows)) {
            $this->error(ucfirst(__('laravel-crm::lang.import_no_data')));

            return;
        }

        $this->totalToProcess = count($rows);
        $this->pendingOffset = 0;
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->processing = true;

        $this->dispatch('crm-process-import-chunk');
    }

    public function processNextChunk(): void
    {
        if (! $this->processing) {
            return;
        }

        $rows = session(self::SESSION_KEY, []);
        $chunk = array_slice($rows, $this->pendingOffset, self::CHUNK_SIZE);

        if (empty($chunk)) {
            $this->finishImport();

            return;
        }

        foreach ($chunk as $row) {
            $this->pendingOffset++;

            if (! empty($row['errors'])) {
                $this->skippedCount++;

                continue;
            }

            try {
                $organizationId = $this->resolveOrganizationId($row['organization_name'] ?? '');

                $person = Person::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'title' => $row['title'] ?: null,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'] ?: null,
                    'description' => $row['description'] ?: null,
                    'organization_id' => $organizationId,
                    'user_owner_id' => auth()->id(),
                ]);

                if (! empty($row['email'])) {
                    $person->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $row['email'],
                        'primary' => 1,
                    ]);
                }

                if (! empty($row['phone'])) {
                    $person->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $row['phone'],
                        'primary' => 1,
                    ]);
                }
            } catch (UniqueConstraintViolationException) {
                $this->skippedCount++;

                continue;
            }

            $this->importedCount++;
        }

        if ($this->pendingOffset < $this->totalToProcess) {
            $this->dispatch('crm-process-import-chunk');
        } else {
            $this->finishImport();
        }
    }

    /** Find-or-create an organization by name (encryption-aware lookup). */
    private function resolveOrganizationId(string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        if ($this->organizationCache === null) {
            $this->organizationCache = [];
            foreach (Organization::all() as $org) {
                $key = mb_strtolower(trim((string) $org->name));
                if ($key !== '' && ! isset($this->organizationCache[$key])) {
                    $this->organizationCache[$key] = $org->id;
                }
            }
        }

        $key = mb_strtolower($name);

        if (isset($this->organizationCache[$key])) {
            return $this->organizationCache[$key];
        }

        $org = Organization::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $name,
            'user_owner_id' => auth()->id(),
        ]);

        $this->organizationCache[$key] = $org->id;

        return $org->id;
    }

    private function finishImport(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->processing = false;
        $this->hasPreview = false;
        $this->imported = true;
        $this->page = 1;

        $this->success(
            ucfirst(__('laravel-crm::lang.import_complete')).': '.
            $this->importedCount.' '.__('laravel-crm::lang.imported').', '.
            $this->skippedCount.' '.__('laravel-crm::lang.skipped')
        );
    }

    public function resetForm(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->hasPreview = false;
        $this->processing = false;
        $this->imported = false;
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->pendingOffset = 0;
        $this->totalToProcess = 0;
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $total = count(session(self::SESSION_KEY, []));
        $lastPage = (int) ceil($total / self::PER_PAGE);

        if ($this->page < $lastPage) {
            $this->page++;
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function render()
    {
        $allRows = $this->hasPreview ? session(self::SESSION_KEY, []) : [];
        $total = count($allRows);
        $lastPage = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;
        $pageRows = array_slice($allRows, ($this->page - 1) * self::PER_PAGE, self::PER_PAGE);

        return view('laravel-crm::livewire.people.person-import', [
            'previewRows' => $pageRows,
            'totalRows' => $total,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'perPage' => self::PER_PAGE,
        ]);
    }
}
