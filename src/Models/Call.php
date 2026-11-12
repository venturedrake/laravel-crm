<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasCrmFields;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class Call extends Model
{
    use BelongsToTeams;
    use HasCrmFields;
    use HasGlobalSettings;
    use SearchFilters;
    use SoftDeletes;

    /**
     * A call the team made.
     */
    public const OUTBOUND = 'outbound';

    /**
     * A call received from the contact.
     */
    public const INBOUND = 'inbound';

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'finish_at' => 'datetime',
    ];

    protected $searchable = [
        'name',
        'description',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'calls';
    }

    /**
     * Get all of the owning callable models.
     */
    public function callable()
    {
        return $this->morphTo('callable');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'user_deleted_id');
    }

    public function restoredByUser()
    {
        return $this->belongsTo(User::class, 'user_restored_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'user_assigned_id');
    }

    public function activity()
    {
        return $this->morphOne(Activity::class, 'recordable');
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * The people on the call. Guests and team members share the contacts
     * relation and are told apart by what the link points at.
     */
    public function guests()
    {
        return $this->contacts()->where('entityable_type', (new Person)->getMorphClass());
    }

    /**
     * The CRM users on the call.
     */
    public function teamMembers()
    {
        return $this->contacts()->where('entityable_type', static::userMorphClass());
    }

    /**
     * @param  array<int, int|string>  $personIds
     */
    public function syncGuests(array $personIds): void
    {
        $this->syncParticipants('guests', Person::whereIn('id', $personIds)->get());
    }

    /**
     * @param  array<int, int|string>  $userIds
     */
    public function syncTeamMembers(array $userIds): void
    {
        $userModel = static::userModel();

        $this->syncParticipants('teamMembers', $userIds === [] ? collect() : $userModel::whereIn('id', $userIds)->get());
    }

    /**
     * The host application's user model, and the name it is stored under in
     * a polymorphic link.
     */
    /**
     * How long the call ran, for the summary line: minutes under an hour,
     * hours and minutes from an hour up. Null without both times.
     */
    public function durationLabel(): ?string
    {
        if (! $this->start_at || ! $this->finish_at || $this->finish_at->lt($this->start_at)) {
            return null;
        }

        $total = (int) round($this->start_at->diffInSeconds($this->finish_at) / 60);
        $hours = intdiv($total, 60);
        $minutes = $total % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.__('laravel-crm::lang.'.($hours === 1 ? 'hour' : 'hours'));
        }

        if ($minutes > 0 || $hours === 0) {
            $parts[] = $minutes.' '.__('laravel-crm::lang.'.($minutes === 1 ? 'minute' : 'minutes'));
        }

        return implode(' ', $parts);
    }

    /**
     * Calls logged before the direction was recorded count as made.
     */
    public function isOutbound(): bool
    {
        return $this->direction !== self::INBOUND;
    }

    public static function userModel(): string
    {
        return config('auth.providers.users.model') ?: User::class;
    }

    public static function userMorphClass(): string
    {
        $userModel = static::userModel();

        return (new $userModel)->getMorphClass();
    }

    /**
     * Make one kind of participant match the given records: links no longer
     * wanted are removed, missing ones are added, the other kind is untouched.
     */
    protected function syncParticipants(string $relation, $records): void
    {
        $this->{$relation}()->whereNotIn('entityable_id', $records->map->getKey()->all())->get()->each->delete();

        foreach ($records as $record) {
            $this->contacts()->firstOrCreate([
                'entityable_type' => $record->getMorphClass(),
                'entityable_id' => $record->getKey(),
            ]);
        }
    }
}
