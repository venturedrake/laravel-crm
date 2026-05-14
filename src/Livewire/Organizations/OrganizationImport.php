<?php

namespace VentureDrake\LaravelCrm\Livewire\Organizations;

use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Component;
use Mary\Traits\Toast;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Organization;

class OrganizationImport extends Component
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

    protected const SESSION_KEY = 'crm_organization_import_preview';

    /** Cache of existing org names (lowercased) to detect duplicates within the file. */
    private ?array $existingNames = null;

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

            $key = mb_strtolower(trim($row['name']));

            if ($this->nameExists($key)) {
                $this->skippedCount++;

                continue;
            }

            try {
                $organization = Organization::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'name' => $row['name'],
                    'vat_number' => $row['vat_number'] ?: null,
                    'website_url' => $row['website_url'] ?: null,
                    'description' => $row['description'] ?: null,
                    'user_owner_id' => auth()->id(),
                ]);

                if (! empty($row['email'])) {
                    $organization->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $row['email'],
                        'primary' => 1,
                    ]);
                }

                if (! empty($row['phone'])) {
                    $organization->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $row['phone'],
                        'primary' => 1,
                    ]);
                }

                $this->existingNames[$key] = true;
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

    private function nameExists(string $key): bool
    {
        if ($this->existingNames === null) {
            $this->existingNames = [];
            foreach (Organization::all() as $org) {
                $name = mb_strtolower(trim((string) $org->name));
                if ($name !== '') {
                    $this->existingNames[$name] = true;
                }
            }
        }

        return isset($this->existingNames[$key]);
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

        return view('laravel-crm::livewire.organizations.organization-import', [
            'previewRows' => $pageRows,
            'totalRows' => $total,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'perPage' => self::PER_PAGE,
        ]);
    }
}
