<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Jobs\SendImportPasswordReset;
use VentureDrake\LaravelCrm\Models\Role;

class UserImport extends Component
{
    use Toast;

    /** True when the session holds parsed preview rows ready to confirm. */
    public bool $hasPreview = false;

    public bool $processing = false;

    public bool $imported = false;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public int $pendingOffset = 0;

    public int $totalToProcess = 0;

    public int $page = 1;

    /** Role ID to assign to rows that have no role in the CSV. */
    public string $defaultRole = '';

    protected const PER_PAGE = 50;

    protected const CHUNK_SIZE = 25;

    public function mount(): void
    {
        $this->hasPreview = session()->has('crm_user_import_preview');
    }

    /** Called from the confirmation modal — sets up state and kicks off the first chunk. */
    public function startImport(): void
    {
        $rows = session('crm_user_import_preview', []);

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

    /** Processes the next chunk of rows and dispatches another event if there is more work to do. */
    public function processNextChunk(): void
    {
        if (! $this->processing) {
            return;
        }

        $rows = session('crm_user_import_preview', []);
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

            if (User::where('email', $row['email'])->exists()) {
                $this->skippedCount++;

                continue;
            }

            try {
                $now = now();
                $user = User::forceCreate([
                    'name'             => $row['name'],
                    'email'            => $row['email'],
                    'password'         => Hash::make(Str::password(length: 16, letters: true, numbers: true, symbols: true, spaces: false)),
                    'crm_access'       => $row['crm_access'],
                    'mailing_list'     => $row['mailing_list'] ?? 1,
                    'email_verified_at'=> ! empty($row['email_verified_at']) ? $row['email_verified_at'] : $now,
                    'created_at'       => ! empty($row['created_at']) ? $row['created_at'] : $now,
                    'updated_at'       => ! empty($row['updated_at']) ? $row['updated_at'] : $now,
                ]);

                // Backdate last_online_at directly — Eloquent guards this column
                if (! empty($row['last_online_at'])) {
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['last_online_at' => $row['last_online_at']]);
                }
            } catch (UniqueConstraintViolationException) {
                $this->skippedCount++;

                continue;
            }

            $roleName = $row['role'] ?? '';

            if (! empty($roleName)) {
                $role = Role::where('name', $roleName)->first();
            } elseif ($this->defaultRole) {
                $role = Role::find($this->defaultRole);
            } else {
                $role = null;
            }

            if ($role) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) {
                    $user->removeRole($removeRole);
                }
                $user->assignRole($role);
            }

            if (config('laravel-crm.teams')) {
                if ($team = auth()->user()->currentTeam) {
                    DB::table('team_user')->insert([
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'editor',
                    ]);

                    $user->forceFill(['current_team_id' => $team->id])->save();
                }
            }

            SendImportPasswordReset::dispatch($user->email);

            $this->importedCount++;
        }

        if ($this->pendingOffset < $this->totalToProcess) {
            $this->dispatch('crm-process-import-chunk');
        } else {
            $this->finishImport();
        }
    }

    private function finishImport(): void
    {
        session()->forget('crm_user_import_preview');
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
        session()->forget('crm_user_import_preview');
        $this->hasPreview = false;
        $this->processing = false;
        $this->imported = false;
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->pendingOffset = 0;
        $this->totalToProcess = 0;
        $this->page = 1;
        $this->defaultRole = '';
    }

    public function nextPage(): void
    {
        $total = count(session('crm_user_import_preview', []));
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
        $allRows = $this->hasPreview ? session('crm_user_import_preview', []) : [];
        $total = count($allRows);
        $lastPage = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;
        $pageRows = array_slice($allRows, ($this->page - 1) * self::PER_PAGE, self::PER_PAGE);

        $roles = Role::crm()
            ->when(config('laravel-crm.teams'), fn ($q) => $q->where('team_id', auth()->user()->currentTeam->id))
            ->get(['id', 'name'])
            ->map(fn ($r) => ['id' => (string) $r->id, 'name' => $r->name])
            ->prepend(['id' => '', 'name' => '— '.ucfirst(__('laravel-crm::lang.no_default_role')).' —'])
            ->values()
            ->all();

        return view('laravel-crm::livewire.users.user-import', [
            'previewRows' => $pageRows,
            'totalRows' => $total,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'perPage' => self::PER_PAGE,
            'roles' => $roles,
        ]);
    }
}
