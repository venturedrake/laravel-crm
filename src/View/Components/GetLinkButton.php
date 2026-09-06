<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;
use VentureDrake\LaravelCrm\Support\PortalLink;

/**
 * The link button that opens the "get link" modal.
 *
 * Stateless in the same way as <x-crm-pdf-preview-button />, and for the same
 * reason: it sits on every row of an index table, so it can only fire an event
 * at the single modal instance in the layout. Giving each row its own modal
 * would mean N modals per page.
 *
 * The signed URL is minted here, at render time, rather than on the click —
 * the modal has no way to authorize a link request that arrives from the
 * browser without re-resolving the record, and this way the URL is already
 * covered by whatever @can() block the button was placed inside.
 */
class GetLinkButton extends Component
{
    public string $url;

    public string $type;

    public string $id;

    public ?string $title;

    public bool $canMarkSent;

    /**
     * Create a new component instance.
     */
    public function __construct(public Model $model)
    {
        $this->url = PortalLink::for($model);
        $this->type = PortalLink::type($model);
        $this->id = $model->external_id;
        $this->title = $model->title ?? null;

        // Hidden once the record is already sent — re-ticking it is a no-op,
        // and quotes have no `sent` column at all.
        //
        // Gated on the same ability GetLink::confirm() authorizes against.
        // The buttons sit inside @can('view crm invoices') blocks, but the
        // tick is a write, so it needs `edit`: without this a view-only user
        // gets a pre-ticked box and a 403 from the modal's OK button.
        $this->canMarkSent = PortalLink::marksSent($model)
            && ! $model->sent
            && Gate::allows('update', $model);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('laravel-crm::components.get-link-button');
    }
}
