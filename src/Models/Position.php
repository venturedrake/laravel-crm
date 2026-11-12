<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

/**
 * One job a person holds at an organisation.
 *
 * A position is current while `end_date` is null or in the future. `title` is
 * not encrypted, so it can be searched and matched in SQL whatever
 * `laravel-crm.encrypt_db_fields` is set to.
 */
class Position extends Model
{
    use BelongsToTeams;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'positions';
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trim((string) $value);
    }

    public function person()
    {
        return $this->belongsTo(Person::class)->withTrashed();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class)->withTrashed();
    }

    /**
     * Positions still held on `$date` (today by default).
     */
    public function scopeCurrent(Builder $query, $date = null)
    {
        $date = ($date ? Carbon::parse($date) : now())->toDateString();

        return $query->where(function (Builder $query) use ($date) {
            $query->whereNull($this->getTable().'.end_date')
                ->orWhereDate($this->getTable().'.end_date', '>', $date);
        });
    }

    /**
     * Positions that had ended by `$date` (today by default).
     */
    public function scopePast(Builder $query, $date = null)
    {
        $date = ($date ? Carbon::parse($date) : now())->toDateString();

        return $query->whereNotNull($this->getTable().'.end_date')
            ->whereDate($this->getTable().'.end_date', '<=', $date);
    }

    /**
     * Positions whose title is `$title`, ignoring case and surrounding spaces.
     */
    public function scopeTitled(Builder $query, string $title)
    {
        return $query->whereRaw('LOWER('.$query->getQuery()->getGrammar()->wrap($this->getTable().'.title').') = ?', [mb_strtolower(trim($title))]);
    }

    public function isCurrent(): bool
    {
        return $this->end_date === null || $this->end_date->isAfter(now()->startOfDay());
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
}
