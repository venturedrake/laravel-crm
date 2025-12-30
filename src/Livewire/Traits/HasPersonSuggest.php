<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Person;

trait HasPersonSuggest
{
    public $people;

    public $showPeople = false;

    public function searchPeople()
    {
        if (! empty($this->person_name)) {
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

            if ($this->people->count() > 0) {
                $this->showPeople = true;
            }
        } else {
            $this->showPeople = false;
        }
    }

    public function linkPerson($id)
    {

        if ($person = Person::find($id)) {
            $this->person_id = $id;
            $this->person_name = $person->name;

            if (! $this->organization_name) {
                $this->generateLeadString($person->name);
            }
        }

        $this->showPeople = false;
    }

    public function hidePeople()
    {
        $this->showPeople = false;
    }
}
