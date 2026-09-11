<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;

/**
 * Eager-loadable counterparts to getPrimaryEmail()/getPrimaryPhone()/
 * getPrimaryAddress().
 *
 * Those three are plain methods that build a fresh query every call, so `with()`
 * cannot reach them and an index page pays one query per row per detail — 50 of
 * the 125 row queries on /people came from the email and phone columns alone.
 *
 * The morphOne relations below express the same "the row flagged primary" and
 * can be eager loaded. The getters keep their signatures but prefer the loaded
 * relation when the calling query loaded it, so every existing caller — show
 * pages, PDF templates, services — gets the saving without being touched.
 */
trait HasPrimaryContactDetails
{
    public function primaryEmail(): MorphOne
    {
        return $this->morphOne(Email::class, 'emailable')->where('primary', 1);
    }

    public function primaryPhone(): MorphOne
    {
        return $this->morphOne(Phone::class, 'phoneable')->where('primary', 1);
    }

    public function primaryAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')->where('primary', 1);
    }

    public function getPrimaryEmail()
    {
        return $this->primaryContactDetail('primaryEmail', 'emails');
    }

    public function getPrimaryPhone()
    {
        return $this->primaryContactDetail('primaryPhone', 'phones');
    }

    public function getPrimaryAddress()
    {
        return $this->primaryContactDetail('primaryAddress', 'addresses');
    }

    /**
     * The primary record for a detail, off the eager-loaded relation when one
     * is there and out of the database when it isn't.
     *
     * relationLoaded() rather than a null check, because "loaded and empty" is
     * a real answer — a contact with no primary email should not fall through
     * to a query that will return the same null.
     *
     * @param  string  $relation  the morphOne on this model, e.g. primaryEmail
     * @param  string  $fallback  the morphMany to query, e.g. emails
     */
    protected function primaryContactDetail(string $relation, string $fallback)
    {
        if ($this->relationLoaded($relation)) {
            return $this->getRelation($relation);
        }

        return $this->{$fallback}()->where('primary', 1)->first();
    }
}
