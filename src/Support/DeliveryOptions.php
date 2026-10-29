<?php

namespace VentureDrake\LaravelCrm\Support;

use Illuminate\Support\Facades\Lang;

/**
 * Install-wide delivery screen options, read from
 * config('laravel-crm.deliveries'). Every key is optional and defaults to
 * today's screens.
 */
class DeliveryOptions
{
    public const DEFAULT_EXPECTED_LABEL = 'delivery_expected';

    /**
     * The delivery PDFs have always headed the expected date "Delivery date",
     * so they keep that until a host configures a label of its own.
     */
    public const DEFAULT_PDF_EXPECTED_LABEL = 'delivery_date';

    public static function showsDeliveredOn(): bool
    {
        return (bool) config('laravel-crm.deliveries.delivered_on', true);
    }

    /**
     * The laravel-crm::lang key that labels delivery_expected on screen,
     * falling back to the default when the configured key does not exist.
     */
    public static function expectedLabelKey(): string
    {
        $key = config('laravel-crm.deliveries.delivery_expected_label');

        if (! is_string($key) || $key === '' || ! Lang::has('laravel-crm::lang.'.$key)) {
            return self::DEFAULT_EXPECTED_LABEL;
        }

        return $key;
    }

    /**
     * The lang key that labels delivery_expected on the delivery PDFs.
     */
    public static function pdfExpectedLabelKey(): string
    {
        $key = self::expectedLabelKey();

        return $key === self::DEFAULT_EXPECTED_LABEL ? self::DEFAULT_PDF_EXPECTED_LABEL : $key;
    }

    public static function expectedLabel(): string
    {
        return __('laravel-crm::lang.'.self::expectedLabelKey());
    }

    public static function pdfExpectedLabel(): string
    {
        return __('laravel-crm::lang.'.self::pdfExpectedLabelKey());
    }
}
