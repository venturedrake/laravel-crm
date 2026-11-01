<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * A line's amount and tax, in integer cents.
 *
 * The form, the services and the CheckAmount helpers all price a line, and
 * they used to do it three different ways. The form multiplied float dollars
 * and rounded to 2dp, so 333.33 x 2.5 - exactly 83332.5 cents - came out as
 * 833.32 (the float product is 833.3249999...), while the check rounded the
 * same line half-up to 83333 and flagged the quote as broken.
 *
 * Here the quantity is scaled to its stored thousandths first, so the product
 * is an exact integer and a half-cent boundary rounds half-up every time.
 */
class LineAmount
{
    /**
     * Price (in cents) times quantity, rounded to the cent.
     */
    public static function amountCents($priceCents, $quantity): int
    {
        $quantityThousandths = (int) round(Quantity::toFloat($quantity) * 1000);

        return (int) round(((float) $priceCents * $quantityThousandths) / 1000);
    }

    /**
     * Tax on a line amount (in cents), rounded to the cent.
     *
     * The inner round to 6dp strips the float error a fractional rate picks
     * up, so a result that is really on the half cent is not read as a hair
     * below it.
     */
    public static function taxCents(int $amountCents, $rate): int
    {
        return (int) round(round($amountCents * (float) $rate / 100, 6));
    }

    /**
     * Amount and tax in cents for a line typed into the form, where the unit
     * price arrives in dollars and possibly masked ("1,200.00").
     *
     * @return array{amount: int, tax: int}
     */
    public static function forForm($unitPrice, $quantity, $rate): array
    {
        $amount = self::amountCents(Money::toInteger($unitPrice) ?? 0, $quantity);

        return [
            'amount' => $amount,
            'tax' => self::taxCents($amount, $rate ?? 0),
        ];
    }
}
