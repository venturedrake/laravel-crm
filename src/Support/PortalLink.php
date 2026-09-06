<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;

/**
 * Signed links to the public portal pages.
 *
 * Three document types have a portal page a recipient can open without an
 * account — quotes, invoices and purchase orders — and each one was minting
 * its own temporary signed URL inside the matching Send component, six copies
 * of the same three lines across the current and legacy Livewire trees. The
 * expiry in particular only meant anything if all six agreed on it.
 *
 * The route parameter name differs per type (`invoice`, `quote`,
 * `purchaseOrder`), which is the detail that makes a naive
 * `route($name, [$type => $model])` wrong, so the mapping lives here too.
 *
 * Orders and deliveries are deliberately absent: they have PDF previews but no
 * portal route, so there is nothing to link to.
 */
class PortalLink
{
    /**
     * How long a portal link stays valid.
     *
     * Matches the expiry the emailed link has always carried — a "get link"
     * copied out of the CRM and an emailed link are the same link, so they
     * must not outlive each other.
     */
    public const DAYS = 14;

    /**
     * Portal type slug => the model, route and route parameter behind it.
     *
     * The slug is what crosses the wire to the modal, so it is also the
     * whitelist that keeps a raw class name off the request.
     */
    private const TYPES = [
        'invoice' => [
            'model' => Invoice::class,
            'route' => 'laravel-crm.portal.invoices.show',
            'parameter' => 'invoice',
            'marks_sent' => true,
        ],
        'quote' => [
            'model' => Quote::class,
            'route' => 'laravel-crm.portal.quotes.show',
            'parameter' => 'quote',
            // Quotes carry no `sent` column, so there is nothing to tick.
            'marks_sent' => false,
        ],
        'purchase-order' => [
            'model' => PurchaseOrder::class,
            'route' => 'laravel-crm.portal.purchase-orders.show',
            'parameter' => 'purchaseOrder',
            'marks_sent' => true,
        ],
    ];

    /**
     * Mint a temporary signed portal URL for the given record.
     *
     * @throws InvalidArgumentException when the model has no portal page
     */
    public static function for(Model $model): string
    {
        $type = self::type($model);

        if ($type === null) {
            throw new InvalidArgumentException(
                'No portal route exists for '.get_class($model).'.'
            );
        }

        return URL::temporarySignedRoute(
            self::TYPES[$type]['route'],
            now()->addDays(self::DAYS),
            [self::TYPES[$type]['parameter'] => $model]
        );
    }

    /**
     * Is there a portal page for this record?
     */
    public static function supports(Model $model): bool
    {
        return self::type($model) !== null;
    }

    /**
     * The portal type slug for a record, or null if it has no portal page.
     *
     * Matched with instanceof rather than an exact class lookup so a host app
     * that swaps in its own subclass still resolves.
     */
    public static function type(Model $model): ?string
    {
        foreach (self::TYPES as $type => $config) {
            if ($model instanceof $config['model']) {
                return $type;
            }
        }

        return null;
    }

    /**
     * The model class behind a type slug, or null if the slug is unknown.
     *
     * This is the whitelist the modal resolves through: the slug arrives from
     * the browser, so it must never be treated as a class name directly.
     */
    public static function modelFor(string $type): ?string
    {
        return self::TYPES[$type]['model'] ?? null;
    }

    /**
     * Does this record have a `sent` flag the "mark as sent" tick can set?
     */
    public static function marksSent(Model $model): bool
    {
        $type = self::type($model);

        return $type !== null && self::TYPES[$type]['marks_sent'];
    }
}
