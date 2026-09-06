<?php

namespace VentureDrake\LaravelCrm\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;

class SettingService
{
    /**
     * Counter appended to every cache key. Bumping it orphans every team's
     * entry at once, which is how forgetCache() invalidates the whole map
     * without having to enumerate teams.
     */
    protected const GENERATION_KEY = 'app.crm-settings.generation';

    protected string $cacheKeyPrefix = 'app.crm-settings';

    protected int $ttl = 3600; // 1 hour (adjust)

    /**
     * Memoised answer to "does crm_settings have a user_id column?".
     *
     * Null until first asked. The service is a singleton, so this costs one
     * information_schema query per request rather than one per read.
     */
    protected ?bool $hasUserColumn = null;

    /**
     * Memoised answer to "does crm_settings exist at all?".
     *
     * Same lifecycle as hasUserColumn: once per request, not once per caller.
     */
    protected ?bool $tableExists = null;

    /**
     * The resolved map for this request, keyed by the cache key it came from.
     *
     * SettingsComposer is registered against every view, so a page render asks
     * for the map hundreds of times. Without this every one of those reads
     * costs two round-trips to the cache store — one for the generation
     * counter, one for the map — which is free on the array driver and ~3000
     * Redis calls per page on a real one.
     *
     * Keyed rather than a single slot so a team switch mid-request lands on a
     * different entry instead of serving the previous tenant's map, and
     * dropped wholesale by forgetCache() so a write is visible to the rest of
     * the request that made it.
     */
    protected array $memo = [];

    /**
     * Request-lifetime copy of the generation counter.
     *
     * Same reasoning as $memo: cacheKey() is called on every read and the
     * counter only moves when forgetCache() moves it, which resets this.
     */
    protected ?int $generation = null;

    /**
     * The global settings map, keyed by name.
     *
     * Scoped to rows with a null user_id so a per-user row can never shadow the
     * global value of the same name — pluck() keys by name, so without this an
     * arbitrary user's row would win for every reader of the cached map.
     *
     * The hasUserColumn() check lives inside the closure so information_schema
     * is queried at most once per TTL rather than on every request, and so
     * hosts that never ran add_user_to_laravel_crm_settings_table still boot.
     *
     * The query is team-scoped by BelongsToTeamsScope, so the cache it fills
     * must be partitioned the same way — see cacheKey().
     */
    public function all(): array
    {
        $key = $this->cacheKey();

        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        return $this->memo[$key] = Cache::remember($key, $this->ttl, function () {
            return Setting::query()
                ->when(
                    $this->hasUserColumn(),
                    fn ($query) => $query->whereNull('user_id')
                )
                ->pluck('value', 'name')
                ->toArray();
        });
    }

    public function get(string $name, $default = null)
    {
        return Arr::get($this->all(), $name, $default);
    }

    public function first(string $name)
    {
        return Setting::where('name', $name)->first();
    }

    public function set($name, $value, $label = null)
    {
        return Setting::updateOrCreate([
            'name' => $name,
        ], [
            'value' => $value,
            'label' => $label,
        ]);
    }

    /**
     * Read a setting scoped to a single user.
     *
     * Deliberately a direct query rather than a cache read: the cached map in
     * all() holds global rows only, and per-user rows are written and read back
     * within the same request (a dismissal, for example), so a cached value
     * would be stale the moment it mattered.
     *
     * Setting's BelongsToTeams global scope means this is team-scoped for free.
     *
     * Guarded on hasUserColumn() for the same reason all() is: a host that has
     * not run add_user_to_laravel_crm_settings_table is exactly the behind-schema
     * install the system check reports on, so this must degrade rather than
     * throw on the way to rendering that report.
     */
    public function getForUser($userId, string $name, $default = null)
    {
        if (! $this->hasUserColumn()) {
            return $default;
        }

        $setting = Setting::query()
            ->where('user_id', $userId)
            ->where('name', $name)
            ->first();

        // Distinguish "no row" from "row holding a null value" — value is
        // nullable, so ?? would collapse the two.
        return $setting ? $setting->value : $default;
    }

    /**
     * Write a setting scoped to a single user, keyed on user_id plus name.
     *
     * Setting guards only `id`, so user_id mass-assigns without a fillable
     * change, and BelongsToTeams stamps team_id on creation.
     *
     * Returns null when the column is missing — the caller loses the write, but
     * a dismissal that cannot be stored is better than a fatal on every page.
     */
    public function setForUser($userId, string $name, $value)
    {
        if (! $this->hasUserColumn()) {
            return null;
        }

        return Setting::updateOrCreate([
            'user_id' => $userId,
            'name' => $name,
        ], [
            'value' => $value,
        ]);
    }

    /**
     * Write an install-wide setting — one that describes the schema or the
     * release rather than anything a team owns.
     *
     * The team scope is dropped deliberately. These rows are written from the
     * console (`laravelcrm:install`, `laravelcrm:update`), where there is no
     * authenticated user and so no team to stamp, but read back from web
     * requests where BelongsToTeamsScope pins every Setting query to the
     * current team. A plain set() therefore writes a row the reader cannot see.
     *
     * Every matching row is updated, not just the first, so per-team duplicates
     * left behind by older versions of this package collapse to one value
     * instead of keeping the alert alive forever.
     */
    public function setInstallWide(string $name, $value)
    {
        $rows = Setting::query()
            ->withoutGlobalScope(BelongsToTeamsScope::class)
            ->where('name', $name)
            ->get();

        if ($rows->isEmpty()) {
            return Setting::create([
                'name' => $name,
                'global' => 1,
                'value' => $value,
            ]);
        }

        // Saved model by model rather than through a builder update() so the
        // observer fires and both caches are dropped.
        foreach ($rows as $row) {
            $row->value = $value;
            $row->save();
        }

        return $rows->first();
    }

    /**
     * Drop the cached map for every team, not just the caller's.
     *
     * Bumping the generation counter is what makes that possible on any cache
     * driver: there is no key enumeration, and the next read of any team's key
     * misses because the key itself has changed. Spanning teams is the correct
     * semantics rather than a shortcut — `global = 1` rows (and everything
     * setInstallWide() writes) appear in every team's map, so a write to one
     * of them has to invalidate all of them.
     *
     * The Cache::forget() is belt-and-braces for the caller's own entry, run
     * before the bump so it targets the key that is actually live.
     */
    public function forgetCache(): void
    {
        Cache::forget($this->cacheKey());

        // increment() is a no-op on a missing key for most drivers, so seed it.
        // Seeding belongs here rather than in generation(): reads outnumber
        // writes by orders of magnitude and only the bump needs the key to
        // already exist.
        Cache::add(self::GENERATION_KEY, 0);
        Cache::increment(self::GENERATION_KEY);

        // Re-read the counter and refetch the map on the next call, so a write
        // is visible to the rest of the request that made it.
        $this->generation = null;
        $this->memo = [];
    }

    /**
     * The cache key for the current reader's settings map.
     *
     * Partitioned by team because all() runs through BelongsToTeamsScope: a
     * single shared key would let whichever team warmed the cache first serve
     * its organisation name, ABN and logo to every other team for the rest of
     * the TTL. The team id is resolved exactly the way the scope resolves it
     * (BelongsToTeamsScope::apply) so the partition can never disagree with
     * the query that fills it. Deliberately auth()->user() and not the scope's
     * auth()->hasUser(): the key is computed before Cache::remember runs the
     * query, so resolving the user here is what guarantees the scope sees one
     * too. Reading hasUser() instead could file an unscoped map — every team's
     * rows — under one team's key.
     *
     * The generation counter is part of the key so forgetCache() can reach
     * every team's entry at once.
     */
    public function cacheKey(): string
    {
        $key = $this->cacheKeyPrefix.'.'.$this->generation();

        // The scope also stands down on Nova requests, and the map all() fills
        // there spans every team. Partitioning it under one team's key would
        // hand that team another tenant's organisation name for the rest of
        // the TTL, so the partition has to stand down in exactly the same
        // cases — hence the shared predicate rather than a second copy of the
        // condition.
        if (! config('laravel-crm.teams') || ! BelongsToTeamsScope::appliesToRequest()) {
            return $key;
        }

        $teamId = auth()->user()->currentTeam->id ?? null;

        return $teamId ? $key.'.team.'.$teamId : $key;
    }

    /**
     * Current value of the shared generation counter, defaulting to 0.
     *
     * Memoised for the life of the service (one request, or one queued job —
     * the binding is scoped, not a singleton) because cacheKey() is on the hot
     * path of every settings read and the counter cannot move underneath us
     * without forgetCache() clearing this.
     */
    protected function generation(): int
    {
        return $this->generation ??= (int) Cache::get(self::GENERATION_KEY, 0);
    }

    /**
     * Whether crm_settings carries the user_id column, resolved once per
     * instance. The service is bound as a singleton, so this is once per
     * request rather than once per read.
     */
    protected function hasUserColumn(): bool
    {
        return $this->hasUserColumn ??= Schema::hasColumn((new Setting)->getTable(), 'user_id');
    }

    /**
     * Whether crm_settings exists yet, resolved once per request.
     *
     * Callers that run before the package's migrations — the view composer
     * registered against every view, for one — need this to avoid asking for
     * settings from a table that is not there.
     */
    public function tableExists(): bool
    {
        return $this->tableExists ??= Schema::hasTable((new Setting)->getTable());
    }
}
