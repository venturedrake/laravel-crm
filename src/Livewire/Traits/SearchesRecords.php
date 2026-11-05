<?php

namespace VentureDrake\LaravelCrm\Livewire\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use VentureDrake\LaravelCrm\Support\RecordSearch;

/**
 * The search behind the record lists and boards: the record's number, the columns
 * given, and its contact's name.
 *
 * With encryption off the query must already join the people and organizations
 * tables. With it on, names are ciphertext in the database, so a LIKE on them can
 * only ever match nothing, and the ids are matched in PHP instead, once per request
 * because a board sends every column query through here.
 */
trait SearchesRecords
{
    use SearchesEncryptableContacts;

    protected ?array $contactMatches = null;

    protected function searchRecords(Builder $query, string $term, array $columns = []): void
    {
        $query->where(function (Builder $query) use ($term, $columns) {
            RecordSearch::orNumber($query, $term);

            foreach ($columns as $column) {
                RecordSearch::orLike($query, $query->getModel()->qualifyColumn($column), $term);
            }

            $this->encryptionEnabled()
                ? $this->searchEncryptedContacts($query, $term)
                : $this->searchJoinedContacts($query, $term);
        });
    }

    private function searchEncryptedContacts(Builder $query, string $term): void
    {
        $this->contactMatches ??= [
            'person_id' => $this->matchingPersonIds($term),
            'organization_id' => $this->matchingOrganizationIds($term),
        ];

        foreach ($this->contactMatches as $column => $ids) {
            if ($ids->isNotEmpty()) {
                $query->orWhereIntegerInRaw($query->getModel()->qualifyColumn($column), $ids);
            }
        }
    }

    private function searchJoinedContacts(Builder $query, string $term): void
    {
        $prefix = config('laravel-crm.db_table_prefix');
        $grammar = $query->getQuery()->getGrammar();

        RecordSearch::orLike($query, $prefix.'organizations.name', $term);
        RecordSearch::orLike($query, $prefix.'people.first_name', $term);
        RecordSearch::orLike($query, $prefix.'people.last_name', $term);
        RecordSearch::orLike($query, new Expression(
            'CONCAT('.$grammar->wrap($prefix.'people.first_name').", ' ', ".$grammar->wrap($prefix.'people.last_name').')'
        ), $term);
    }
}
