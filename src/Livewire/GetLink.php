<?php

namespace VentureDrake\LaravelCrm\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use VentureDrake\LaravelCrm\Support\PortalLink;

/**
 * The "get link" modal.
 *
 * Mounted exactly once, in the app layout, so the buttons scattered across
 * show pages and index rows all drive the same instance. Livewire rather than
 * pure Alpine (which is what the PDF preview drawer gets away with) because
 * "mark as sent" is a database write and needs a server round trip.
 *
 * Every public property here is writable from the browser, so nothing on this
 * component may be trusted as an authorization input. `confirm()` re-derives
 * the record from the type/id pair through PortalLink's whitelist and runs it
 * past the policy before writing anything.
 */
class GetLink extends Component
{
    use AuthorizesRequests, Toast;

    public bool $show = false;

    public string $url = '';

    public string $type = '';

    public string $id = '';

    public ?string $title = null;

    public bool $canMarkSent = false;

    public bool $markAsSent = false;

    /**
     * Open the modal for the record the button was rendered against.
     *
     * The payload is everything the button already resolved server-side —
     * re-resolving it here would mean an extra query on every open, and the
     * URL it carries was minted inside whatever @can() block the button sat
     * in.
     */
    #[On('crm-get-link')]
    public function open(array $payload): void
    {
        $this->url = $payload['url'] ?? '';
        $this->type = $payload['type'] ?? '';
        $this->id = $payload['id'] ?? '';
        $this->title = $payload['title'] ?? null;
        $this->canMarkSent = (bool) ($payload['canMarkSent'] ?? false);

        // Pre-ticked, matching the accounting packages this mirrors: someone
        // copying the customer's link is almost always about to send it.
        $this->markAsSent = $this->canMarkSent;

        $this->show = true;
    }

    /**
     * Apply the "mark as sent" tick, if it was left on, and close.
     */
    public function confirm(): void
    {
        $model = $this->markAsSent ? $this->record() : null;

        // marksSent() is re-checked against the resolved record rather than
        // read off $canMarkSent, which arrived from the browser.
        if ($model && PortalLink::marksSent($model)) {
            $this->authorize('update', $model);

            $model->update(['sent' => 1]);

            $this->success(ucfirst(trans('laravel-crm::lang.marked_as_sent')));

            // The badge lives in a different Livewire root (the show page, or
            // the index row this was opened from), which has no reason to
            // re-render on its own.
            $this->dispatch('crm-get-link-sent');
        }

        $this->show = false;
    }

    public function render()
    {
        return view('laravel-crm::livewire.get-link');
    }

    /**
     * Resolve the record from the type slug and external id held in state.
     *
     * The slug is looked up in PortalLink's map rather than used as a class
     * name, so an edited request can only ever name one of the three portal
     * models.
     */
    private function record(): ?Model
    {
        $class = PortalLink::modelFor($this->type);

        if (! $class || $this->id === '') {
            return null;
        }

        return $class::where('external_id', $this->id)->first();
    }
}
