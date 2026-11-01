<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * A document's subtotal and tax, summed from the lines it actually saved.
 *
 * The services used to store whatever header totals the request carried. The
 * line-items form can post those totals out of step with its rows - a row
 * zeroed in a wholesale update kept its amount in the sum - and the service
 * then drops the zero-quantity row, so the header billed for a line the
 * document no longer had.
 */
class DocumentTotals
{
    /**
     * @param  iterable<object>  $lines  saved line models, amounts in cents
     * @return array{subtotal: int, tax: int}
     */
    public static function fromLines(iterable $lines): array
    {
        $subtotal = 0;
        $tax = 0;

        foreach ($lines as $line) {
            // Quote and order lines have no tax_amount mutator, so theirs can
            // be stored as fractional cents - each line rounds on its own.
            $subtotal += (int) round((float) $line->amount);
            $tax += (int) round((float) $line->tax_amount);
        }

        return ['subtotal' => $subtotal, 'tax' => $tax];
    }
}
