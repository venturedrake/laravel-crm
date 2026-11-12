<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasEncryptableFields;

/**
 * One email logged against a record, shown in its Emails tab and on its
 * timeline. Named EmailMessage because Email is a contact's email address.
 */
class EmailMessage extends Model
{
    use BelongsToTeams;
    use HasEncryptableFields;
    use SoftDeletes;

    public const OUTBOUND = 'outbound';

    public const INBOUND = 'inbound';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    protected $encryptable = [
        'from_address',
        'from_name',
        'to',
        'cc',
        'subject',
        'body_html',
        'body_text',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'email_messages';
    }

    /**
     * The record the email was logged against.
     */
    public function emailable()
    {
        return $this->morphTo('emailable');
    }

    /**
     * The other people involved, linked the way call guests are.
     */
    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * Timeline entries: one on the owning record, and one on each other
     * person involved.
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'recordable');
    }

    public function activity()
    {
        return $this->morphOne(Activity::class, 'recordable');
    }

    public function isOutbound(): bool
    {
        return $this->direction !== self::INBOUND;
    }

    /**
     * Who the email was exchanged with: the recipients of a sent email, the
     * sender of a received one.
     */
    public function otherParty(): ?string
    {
        if ($this->isOutbound()) {
            return $this->to ?: null;
        }

        if ($this->from_name && $this->from_address) {
            return $this->from_name.' <'.$this->from_address.'>';
        }

        return ($this->from_name ?: $this->from_address) ?: null;
    }

    /**
     * The timeline title, in place of the generic "{user} created a {type}".
     */
    public function timelineTitle(?string $userName = null): string
    {
        $key = $this->isOutbound() ? 'logged_a_sent_email' : 'logged_a_received_email';

        return trim(($userName ? $userName.' ' : '').__('laravel-crm::lang.'.$key));
    }

    /**
     * When the email was sent or received, as opposed to when it was logged.
     */
    public function timelineDate()
    {
        return $this->sent_at ?? $this->created_at;
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
}
