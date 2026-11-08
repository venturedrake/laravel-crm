<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;

/**
 * Display-only contact relations that still resolve a soft-deleted person or
 * organization, so a show page can say "Name (deleted)" rather than go blank.
 *
 * person() and organization() deliberately stay as they are. The edit forms,
 * the send dialogs and the activity components all read those, and handing
 * them a deleted contact would refill it into a form, address an email to it,
 * or list its activity.
 */
trait HasTrashedContacts
{
    public function personWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id')->withTrashed();
    }

    public function organizationWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id')->withTrashed();
    }
}
