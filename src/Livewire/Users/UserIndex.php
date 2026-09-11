<?php

namespace VentureDrake\LaravelCrm\Livewire\Users;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\UserInvitation;
use VentureDrake\LaravelCrm\Notifications\UserInvitationNotification;
use VentureDrake\LaravelCrm\Support\TeamMembership;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class UserIndex extends Component
{
    use AuthorizesRequests, ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public string $tab = 'users';

    #[Url]
    public string $search = '';

    #[Url]
    public ?array $role_id = [];

    #[Url]
    public ?string $crm_access = null;

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function filterCount(): int
    {
        return (count($this->role_id) > 0 ? 1 : 0)
            + ($this->crm_access !== null && $this->crm_access !== '' ? 1 : 0);
    }

    /**
     * Role options for the filter drawer, computed so a debounced search
     * keystroke reuses them rather than re-querying on every render.
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::crm()->select('id', 'name')->orderBy('name')->get();
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'email', 'label' => ucfirst(__('laravel-crm::lang.email'))],
            ['key' => 'email_verified_at', 'label' => ucwords(__('laravel-crm::lang.email_verified')), 'format' => fn ($row, $field) => ($field) ? $field->format($this->dateFormat) : null],
            ['key' => 'crm_access', 'label' => ucfirst(__('laravel-crm::lang.CRM_Access')), 'format' => fn ($row, $field) => $field ? ucfirst(__('laravel-crm::lang.yes')) : ucfirst(__('laravel-crm::lang.no'))],
            ['key' => 'role', 'label' => ucfirst(__('laravel-crm::lang.role')), 'sortable' => false],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->diffForHumans()],
            ['key' => 'last_online_at', 'label' => ucwords(__('laravel-crm::lang.last_online')), 'format' => fn ($row, $field) => ($field) ? Carbon::parse($field)->diffForHumans() : ucfirst(__('laravel-crm::lang.never'))],

        ];
    }

    public function invitationHeaders(): array
    {
        return [
            ['key' => 'email', 'label' => ucfirst(__('laravel-crm::lang.email'))],
            ['key' => 'role', 'label' => ucfirst(__('laravel-crm::lang.role')), 'sortable' => false],
            ['key' => 'invited_by', 'label' => ucfirst(__('laravel-crm::lang.invited_by')), 'sortable' => false],
            ['key' => 'sent_at', 'label' => ucfirst(__('laravel-crm::lang.sent_at')), 'sortable' => false],
            ['key' => 'last_sent', 'label' => ucfirst(__('laravel-crm::lang.last_sent')), 'sortable' => false],
            ['key' => 'expires', 'label' => ucfirst(__('laravel-crm::lang.expires')), 'sortable' => false],
        ];
    }

    public function users(): LengthAwarePaginator
    {
        // The role cell reads the user's first role; without this it is a query
        // per row.
        return User::with('roles')
            ->when($this->search, function (Builder $q) {
                $q->where('name', 'like', "%$this->search%");
            })
            ->when($this->crm_access !== null && $this->crm_access !== '', fn (Builder $q) => $q->where('crm_access', (bool) $this->crm_access))
            ->when($this->role_id, fn (Builder $q) => $q->whereHas('roles', fn (Builder $q) => $q->where('crm_role', 1)->whereIn('roles.id', $this->role_id)))
            // Mirrors the team scoping in pendingInvitationsQuery(). Without it a
            // Team A admin lists every user in the host application and the delete
            // button then 403s on a row they can see. Users are not team-scoped by a
            // column, so the boundary is the Jetstream `team_user` pivot -- the same
            // table UserController::store() writes to when teams are enabled. When no
            // current team resolves this matches nothing, which fails closed the same
            // way TeamMembership::inCurrentTeam() does.
            ->when(config('laravel-crm.teams'), function (Builder $q) {
                $currentTeam = auth()->user()?->currentTeam;
                $currentTeamId = $currentTeam->id ?? null;

                // A host can enable laravel-crm.teams without shipping Jetstream's
                // pivot (Spark Classic names it `team_users`), in which case there is
                // no queryable membership boundary. Fail open rather than 500 on a
                // read page -- the same call TeamMembership::inCurrentTeam() makes
                // when it cannot probe the host user's teams.
                if (! Schema::hasTable('team_user')) {
                    return;
                }

                $usersTable = (new User)->getTable();

                $q->where(function (Builder $q) use ($currentTeam, $currentTeamId, $usersTable) {
                    $q->whereExists(function ($query) use ($currentTeamId, $usersTable) {
                        $query->select(DB::raw(1))
                            ->from('team_user')
                            ->whereColumn('team_user.user_id', $usersTable.'.id')
                            ->where('team_user.team_id', $currentTeamId);
                    });

                    // Jetstream keeps the team owner out of the pivot entirely --
                    // CreateTeam writes through ownedTeams() and Team::allUsers()
                    // merges the owner back in by hand. Without this the owner,
                    // usually the CRM Owner themselves, vanishes from their own
                    // user list.
                    if ($ownerId = $currentTeam->user_id ?? null) {
                        $q->orWhere($usersTable.'.id', $ownerId);
                    }
                });
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function getInvitationsProperty(): LengthAwarePaginator
    {
        return $this->pendingInvitationsQuery()
            ->latest('last_sent_at')
            ->paginate(25);
    }

    public function resendInvitation(int $id): void
    {
        $this->authorize('create', User::class);

        $invitation = $this->pendingInvitationsQuery()->whereKey($id)->first();

        if (! $invitation) {
            return;
        }

        Notification::route('mail', $invitation->email)
            ->notify(new UserInvitationNotification($invitation));

        $invitation->forceFill(['last_sent_at' => now()])->save();

        $this->success(ucfirst(__('laravel-crm::lang.invitation_resent')));
    }

    public function deleteInvitation(int $id): void
    {
        $this->authorize('create', User::class);

        $invitation = $this->pendingInvitationsQuery()->whereKey($id)->first();

        if (! $invitation) {
            return;
        }

        $invitation->delete();

        $this->success(ucfirst(__('laravel-crm::lang.invitation_deleted')));
    }

    protected function pendingInvitationsQuery(): Builder
    {
        // role and invitedByUser are both rendered per row on the invitations
        // tab.
        return UserInvitation::with(['role', 'invitedByUser'])
            ->whereNull('accepted_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->when(config('laravel-crm.teams'), function (Builder $q) {
                $q->where('team_id', auth()->user()?->currentTeam?->id);
            });
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (! $user) {
            return;
        }

        $this->authorize('delete', $user);

        // Self-deletion is a user error rather than a permission failure -- an Owner
        // legitimately holds 'delete crm users', and letting them remove their own
        // account can orphan the last Owner. Surface it as a toast, not a 403.
        if ((int) $user->getKey() === (int) auth()->id()) {
            $this->error(ucfirst(trans('laravel-crm::lang.user_cannot_delete_self')));

            return;
        }

        abort_unless(TeamMembership::inCurrentTeam($user), 403);

        $user->delete();

        $this->success(ucfirst(trans('laravel-crm::lang.user_deleted')));
    }

    public function render()
    {
        return view('laravel-crm::livewire.users.user-index', [
            'roles' => $this->roles,
            'filterCount' => $this->filterCount(),
            'headers' => $this->headers(),
            'users' => $this->users(),
            'invitationHeaders' => $this->invitationHeaders(),
        ]);
    }
}
