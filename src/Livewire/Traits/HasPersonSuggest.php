<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Person;

trait HasPersonSuggest
{
    use SearchesEncryptableContacts;

    public $people;

    public $showPeople = false;

    public function searchPeople()
    {
        if (! empty($this->person_name)) {
            if ($this->encryptionEnabled()) {
                // Names are stored encrypted, so neither LIKE nor ORDER BY on
                // the columns means anything — match and sort on decrypted values.
                $this->people = Person::whereIn('id', $this->matchingPersonIds($this->person_name))
                    ->get()
                    ->sortBy([
                        fn ($a, $b) => strtolower((string) $a->first_name) <=> strtolower((string) $b->first_name),
                        fn ($a, $b) => strtolower((string) $a->last_name) <=> strtolower((string) $b->last_name),
                    ])
                    ->take(10)
                    ->values();
            } else {
                $term = '%'.str_replace(' ', '%', $this->person_name).'%'; // allows matching across space boundaries

                $this->people = Person::orderby('first_name', 'asc')
                    ->select('*')
                    ->where(function ($q) use ($term) {
                        $q->where(DB::raw("CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))"), 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    })
                    ->limit(10)
                    ->get();
            }

            $this->showPeople = $this->people->isNotEmpty();
        } else {
            $this->showPeople = false;
        }
    }

    public function linkPerson($id)
    {
        if ($person = Person::find($id)) {
            $this->person_id = $id;
            $this->person_name = $person->name;

            if (property_exists($this, 'organization_name') && ! $this->organization_name && method_exists($this, 'generateTitleString')) {
                $this->generateTitleString($person->name);
            }
        }

        $this->showPeople = false;
    }

    public function hidePeople()
    {
        $this->showPeople = false;
    }
}
