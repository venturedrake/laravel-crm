<?php

namespace VentureDrake\LaravelCrm\Livewire\EmailCampaigns\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use VentureDrake\LaravelCrm\Livewire\Traits\SearchesEncryptableContacts;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Support\EmailCampaignAudience;
use VentureDrake\LaravelCrm\Support\RecordSearch;

/**
 * Audience selection and the send step shared by the create and edit pages.
 *
 * New and edited campaigns are saved as drafts unless the user picks a send
 * mode. "Send now" needs a second, confirmed save that shows the recipient
 * count first.
 *
 * Built for large CRMs: the recipient count loads after the page does
 * (wire:init), and people and organizations are searched on the server, so the
 * page only ever holds the selected records plus one page of matches.
 */
trait HasEmailCampaignAudience
{
    use SearchesEncryptableContacts;

    public const AUDIENCE_SEARCH_LIMIT = 20;

    public string $audience_type = EmailCampaign::AUDIENCE_ALL;

    public array $audience_ids = [];

    public array $audienceOptions = [];

    public string $send_mode = 'draft';

    public ?string $scheduled_at = null;

    /**
     * Null until loadRecipientCount() has run.
     */
    public ?int $recipientCount = null;

    public bool $confirmingSend = false;

    /**
     * Whoever could open the form may read its audience.
     */
    abstract protected function authorizeAudienceRead(): void;

    /**
     * Called by wire:init, so the page renders before the count is worked out.
     */
    public function loadRecipientCount(): void
    {
        $this->authorizeAudienceRead();

        $this->recalculateRecipients();
    }

    /**
     * The search-function of the people and organization pickers.
     */
    public function searchAudience(string $value = ''): void
    {
        $this->authorizeAudienceRead();

        if (! EmailCampaignAudience::isSearchable($this->audience_type)) {
            return;
        }

        // Mary's picker drops a selected value it has no option for, so the
        // selected records stay in the list alongside the matches.
        $this->audienceOptions = EmailCampaignAudience::options(
            $this->audience_type,
            [...$this->audience_ids, ...$this->matchingAudienceIds(trim($value))]
        );
    }

    public function updatedAudienceType(): void
    {
        $this->audience_ids = [];
        $this->resetErrorBag('audience_ids');
        $this->loadAudienceOptions();
        $this->recalculateRecipients();
    }

    public function updatedAudienceIds(): void
    {
        $this->resetErrorBag('audience_ids');
        $this->recalculateRecipients();
    }

    protected function audienceRules(): array
    {
        return [
            'audience_type' => 'required|in:'.implode(',', EmailCampaign::AUDIENCE_TYPES),
            'audience_ids' => 'required_unless:audience_type,'.EmailCampaign::AUDIENCE_ALL.'|array',
            'audience_ids.*' => 'integer',
            'send_mode' => 'required|in:draft,now,schedule',
            'scheduled_at' => 'required_if:send_mode,schedule|nullable|date|after:now',
        ];
    }

    protected function audienceData(): array
    {
        return [
            'audience_type' => $this->audience_type,
            'audience_ids' => $this->selectedAudienceIds(),
        ];
    }

    protected function selectedAudienceIds(): array
    {
        return $this->audience_type === EmailCampaign::AUDIENCE_ALL ? [] : $this->audience_ids;
    }

    protected function recalculateRecipients(): int
    {
        return $this->recipientCount = EmailCampaignAudience::count($this->audience_type, $this->selectedAudienceIds());
    }

    /**
     * The picker options for the current audience type.
     *
     * Labels and pipeline stages are short lists and load in full. People and
     * organizations can run to tens of thousands, so only the selected ones
     * load; the rest arrive through searchAudience().
     */
    protected function loadAudienceOptions(): void
    {
        if ($this->audience_type === EmailCampaign::AUDIENCE_ALL) {
            $this->audienceOptions = [];

            return;
        }

        $this->audienceOptions = EmailCampaignAudience::options(
            $this->audience_type,
            EmailCampaignAudience::isSearchable($this->audience_type) ? $this->audience_ids : null
        );
    }

    /**
     * Ids of the people or organizations whose name contains $term.
     */
    protected function matchingAudienceIds(string $term): array
    {
        if ($term === '') {
            return [];
        }

        $people = $this->audience_type === EmailCampaign::AUDIENCE_PEOPLE;

        if ($this->encryptionEnabled()) {
            $ids = $people ? $this->matchingPersonIds($term) : $this->matchingOrganizationIds($term);

            return $ids->take(self::AUDIENCE_SEARCH_LIMIT)->all();
        }

        if ($people) {
            $grammar = Person::query()->getQuery()->getGrammar();
            $fullName = new Expression('CONCAT('.$grammar->wrap('first_name').", ' ', ".$grammar->wrap('last_name').')');

            return Person::query()
                ->where(function (Builder $query) use ($fullName, $term) {
                    RecordSearch::orLike($query, 'first_name', $term);
                    RecordSearch::orLike($query, 'last_name', $term);
                    RecordSearch::orLike($query, $fullName, $term);
                })
                ->orderBy('first_name')
                ->limit(self::AUDIENCE_SEARCH_LIMIT)
                ->pluck('id')
                ->all();
        }

        return Organization::query()
            ->where(fn (Builder $query) => RecordSearch::orLike($query, 'name', $term))
            ->orderBy('name')
            ->limit(self::AUDIENCE_SEARCH_LIMIT)
            ->pluck('id')
            ->all();
    }

    /**
     * Whether a save may go ahead.
     *
     * Returns false, after flagging an empty audience or opening the confirm
     * modal, when nothing should be written yet. Callers check this before they
     * persist anything, so an unconfirmed "Send now" leaves no draft behind.
     *
     * Only the confirm modal needs the full count. Every other path just needs
     * to know the audience is not empty, which any() answers from the first row.
     */
    protected function readyToSave(bool $confirmed): bool
    {
        $this->confirmingSend = false;

        if ($this->send_mode === 'draft') {
            return true;
        }

        $needsConfirmation = $this->send_mode === 'now' && ! $confirmed;

        $empty = $needsConfirmation
            ? $this->recalculateRecipients() === 0
            : ! EmailCampaignAudience::any($this->audience_type, $this->selectedAudienceIds());

        if ($empty) {
            $this->recipientCount = 0;
            $this->addError('audience_ids', ucfirst(__('laravel-crm::lang.no_recipients_in_audience')));

            return false;
        }

        if ($needsConfirmation) {
            $this->confirmingSend = true;

            return false;
        }

        return true;
    }

    /**
     * The scheduled time to pass to EmailCampaignService::schedule(), or false
     * when the campaign stays a draft.
     */
    protected function sendAt(): string|null|false
    {
        return match ($this->send_mode) {
            'now' => null,
            'schedule' => $this->scheduled_at,
            default => false,
        };
    }

    protected function savedMessage(): string
    {
        return match ($this->send_mode) {
            'now' => ucfirst(__('laravel-crm::lang.campaign_sending')),
            'schedule' => ucfirst(__('laravel-crm::lang.campaign_scheduled')),
            default => ucfirst(__('laravel-crm::lang.campaign_saved_as_draft')),
        };
    }

    protected function audienceViewData(): array
    {
        return [
            'audienceTypeOptions' => EmailCampaignAudience::typeOptions(),
        ];
    }
}
