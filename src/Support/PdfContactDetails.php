<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * Resolves the "From" contact block printed on document PDFs.
 *
 * Historically only invoices had one: `invoice_contact_details` was the sole
 * key with a settings field, and only the invoice call sites read it. When the
 * themed PDF templates landed they copied the invoice blade's "From" block into
 * the quote, order and delivery blades, whose renders then referenced a
 * `$contactDetails` variable their callers never passed — an undefined-variable
 * 500 on every non-classic template.
 *
 * Rather than mint a settings field per doc type, this resolves a chain:
 *
 *   {doc_type}_contact_details  →  pdf_contact_details  →  null
 *
 * The per-doc-type key is checked first so existing hosts keep their exact
 * invoice output. `pdf_contact_details` is the shared fallback that makes the
 * block fillable from one field wherever a blade actually renders it.
 *
 * Which is not everywhere, and the gaps are deliberate — closing either would
 * change the visible output of a document that never carried the block:
 *
 *                            quote  order  delivery  invoice  purchase-order
 *   classic                    -      -       -         yes        -
 *   modern/bold/compact/prof  yes    yes     yes        yes        -
 *
 * Purchase-order layouts pair a Supplier column with a Delivery details one
 * rather than From/To, so no purchase-order blade reads `$contactDetails` on
 * any template. `classic` is the pre-2.4.0 layout reproduced unchanged, where
 * only the invoice blade ever had a From block.
 *
 * Every call site still resolves through here regardless — keeping the 15
 * uniform, and leaving the value in place should those blades ever grow the
 * block — but the combinations marked `-` render nothing today, and
 * `purchase_order_contact_details` remains without a screen that writes it.
 *
 * `filled()` rather than `??` throughout: SettingEdit and SettingController
 * both persist a cleared shared field as an empty-string row rather than
 * deleting it, so `''` is a value this chain genuinely sees. Treating it as
 * "set" would pin the chain on `''` — either swallowing the shared value when
 * a doc-type override was cleared, or returning `''` where callers expect null.
 */
class PdfContactDetails
{
    /**
     * The shared setting used when a doc type has no override of its own.
     */
    public const SHARED_KEY = 'pdf_contact_details';

    /**
     * The per-doc-type setting name for `$docType`.
     *
     * Doc types are hyphenated slugs (`purchase-order`); settings names are
     * snake_case, so the hyphen is normalised.
     */
    public static function settingKey(string $docType): string
    {
        return str_replace('-', '_', $docType).'_contact_details';
    }

    /**
     * Resolve the contact block for `$docType`, ready to drop into a PDF
     * view's `contactDetails` variable.
     */
    public static function for(string $docType): ?string
    {
        $settings = app('laravel-crm.settings');

        if (filled($value = $settings->get(self::settingKey($docType)))) {
            return $value;
        }

        return filled($shared = $settings->get(self::SHARED_KEY)) ? $shared : null;
    }
}
