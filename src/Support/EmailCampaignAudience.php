<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;

/**
 * Resolves who an email campaign goes to.
 *
 * Read-only. It is deliberately not named *Service: the Livewire authorization
 * coverage test treats any Service call in a public action as a write, and the
 * recipient count is recalculated on every audience change.
 */
class EmailCampaignAudience
{
    /**
     * The subscribed person email addresses in the audience.
     *
     * A filtered type with no ids matches nothing. It never falls back to
     * everyone.
     */
    public static function query(string $type, array $ids): Builder
    {
        $query = Email::query()
            ->where('subscribed', true)
            ->where('emailable_type', Person::class)
            ->whereNotNull('address');

        if ($type === EmailCampaign::AUDIENCE_ALL) {
            return $query;
        }

        $ids = self::normaliseIds($ids);

        if ($ids === [] || ! in_array($type, EmailCampaign::AUDIENCE_TYPES, true)) {
            return $query->whereRaw('1 = 0');
        }

        if ($type === EmailCampaign::AUDIENCE_PEOPLE) {
            return $query->whereIn('emailable_id', $ids);
        }

        return $query->whereIn('emailable_id', self::peopleQuery($type, $ids)->select((new Person)->getTable().'.id'));
    }

    /**
     * Call $fn once per unique address in the audience, with an Email model
     * that carries only id, emailable_id and address.
     */
    public static function each(string $type, array $ids, callable $fn): void
    {
        $email = new Email;

        foreach (self::uniqueRows($type, $ids, ['id', 'emailable_id', 'address']) as $row) {
            $fn($email->newFromBuilder($row));
        }
    }

    /**
     * The number of recipients the audience would materialise to.
     */
    public static function count(string $type, array $ids): int
    {
        $count = 0;

        foreach (self::uniqueRows($type, $ids, ['id', 'address']) as $row) {
            $count++;
        }

        return $count;
    }

    /**
     * Whether the audience has at least one recipient. Stops at the first one,
     * so it is cheap even when count() would walk a large list.
     */
    public static function any(string $type, array $ids): bool
    {
        foreach (self::uniqueRows($type, $ids, ['id', 'address']) as $row) {
            return true;
        }

        return false;
    }

    /**
     * Whether the records for this type are picked by searching the server,
     * rather than by loading them all into the page.
     */
    public static function isSearchable(string $type): bool
    {
        return in_array($type, [EmailCampaign::AUDIENCE_PEOPLE, EmailCampaign::AUDIENCE_ORGANIZATIONS], true);
    }

    /**
     * The audience's email rows, one per unique address, read lazily.
     *
     * Rows come back as plain objects with just $columns, not Eloquent models,
     * which keeps a large audience cheap to walk. Addresses may be encrypted,
     * so duplicates can only be found in PHP; they are decrypted here the same
     * way HasEncryptableFields would.
     *
     * @return \Generator<int, object>
     */
    private static function uniqueRows(string $type, array $ids, array $columns): \Generator
    {
        if ($type !== EmailCampaign::AUDIENCE_ALL
            && (self::normaliseIds($ids) === [] || ! in_array($type, EmailCampaign::AUDIENCE_TYPES, true))) {
            return;
        }

        $email = new Email;
        $encrypted = (bool) config('laravel-crm.encrypt_db_fields');
        $seen = [];

        $rows = self::query($type, $ids)->toBase()->select($columns)->lazyById(500, 'id');

        foreach ($rows as $row) {
            $address = (string) $row->address;

            if ($encrypted && trim($address) !== '') {
                $address = (string) $email->decryptField($address);
            }

            $key = strtolower(trim($address));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            yield $row;
        }
    }

    /**
     * A short summary for the show page, e.g. "Labels: VIP, Newsletter".
     */
    public static function describe(EmailCampaign $campaign): string
    {
        $type = $campaign->audience_type ?: EmailCampaign::AUDIENCE_ALL;

        if ($type === EmailCampaign::AUDIENCE_ALL) {
            return ucfirst(__('laravel-crm::lang.all_subscribed_contacts'));
        }

        $ids = self::normaliseIds($campaign->audience_ids ?? []);
        $names = collect($ids === [] ? [] : self::options($type, $ids))->pluck('name');

        $shown = $names->take(5)->implode(', ');

        if ($names->count() > 5) {
            $shown .= ' +'.($names->count() - 5);
        }

        return ucfirst(self::typeLabel($type)).': '.($shown !== '' ? $shown : '—');
    }

    /**
     * The radio options for the audience type.
     *
     * @return list<array{id: string, name: string}>
     */
    public static function typeOptions(): array
    {
        return array_map(fn ($type) => [
            'id' => $type,
            'name' => ucfirst(self::typeLabel($type)),
        ], EmailCampaign::AUDIENCE_TYPES);
    }

    /**
     * The selectable records for a filtered audience type.
     *
     * Names are read through the model accessors, so encrypted names are
     * decrypted, and sorted in PHP for the same reason.
     *
     * Pass $only to load just those records (the show page summary).
     *
     * @return list<array{id: int, name: string}>
     */
    public static function options(string $type, ?array $only = null): array
    {
        $limit = fn (Builder $query) => $only === null
            ? $query
            : $query->whereIn($query->getModel()->getTable().'.id', self::normaliseIds($only));

        $options = match ($type) {
            EmailCampaign::AUDIENCE_LABELS => $limit(Label::query())->get()
                ->map(fn ($label) => ['id' => $label->id, 'name' => (string) $label->name]),
            EmailCampaign::AUDIENCE_ORGANIZATIONS => $limit(Organization::query())->get()
                ->map(fn ($organization) => ['id' => $organization->id, 'name' => (string) $organization->name]),
            EmailCampaign::AUDIENCE_PEOPLE => $limit(Person::query())->get()
                ->map(fn ($person) => ['id' => $person->id, 'name' => (string) $person->name]),
            EmailCampaign::AUDIENCE_PIPELINE_STAGES => $limit(PipelineStage::query())
                ->with('pipeline')
                ->whereHas('pipeline', fn ($query) => $query->whereIn('model', [Lead::class, Deal::class]))
                ->orderBy('pipeline_id')
                ->orderBy('order')
                ->get()
                ->map(fn ($stage) => ['id' => $stage->id, 'name' => $stage->pipeline->name.' — '.$stage->name]),
            default => collect(),
        };

        if ($type !== EmailCampaign::AUDIENCE_PIPELINE_STAGES) {
            $options = $options->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $options->values()->all();
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            EmailCampaign::AUDIENCE_LABELS => __('laravel-crm::lang.labels'),
            EmailCampaign::AUDIENCE_ORGANIZATIONS => __('laravel-crm::lang.organizations'),
            EmailCampaign::AUDIENCE_PIPELINE_STAGES => __('laravel-crm::lang.pipeline_stages'),
            EmailCampaign::AUDIENCE_PEOPLE => __('laravel-crm::lang.people'),
            default => __('laravel-crm::lang.all_subscribed_contacts'),
        };
    }

    /**
     * The people matched by a label, organization or pipeline stage filter.
     */
    protected static function peopleQuery(string $type, array $ids): Builder
    {
        $query = Person::query();

        return match ($type) {
            EmailCampaign::AUDIENCE_LABELS => $query->whereHas(
                'labels',
                fn ($labels) => $labels->whereIn((new Label)->getTable().'.id', $ids)
            ),
            EmailCampaign::AUDIENCE_ORGANIZATIONS => $query->whereIn('organization_id', $ids),
            EmailCampaign::AUDIENCE_PIPELINE_STAGES => $query->where(function ($people) use ($ids) {
                $people->whereHas('leads', fn ($leads) => $leads->whereIn('pipeline_stage_id', $ids))
                    ->orWhereHas('deals', fn ($deals) => $deals->whereIn('pipeline_stage_id', $ids));
            }),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @return list<int>
     */
    protected static function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_map(
            'intval',
            array_filter($ids, fn ($id) => is_numeric($id) && (int) $id > 0)
        )));
    }
}
