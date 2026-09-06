<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * Central registry describing the shipped PDF templates.
 *
 * Provides a single source of truth for the 5 template variants that any of
 * the 5 document types (invoice, order, purchase-order, delivery, quote)
 * can be rendered against. Downstream code resolves a Blade view path via
 * `viewFor($docType, $slug)` — unknown slugs fall back to `defaultSlug()`
 * gracefully so a persisted template preference that references a removed
 * variant never 500s the download route.
 */
class PdfTemplateRegistry
{
    public const DEFAULT_SLUG = 'modern';

    /**
     * The 5 document types PDFs can be rendered for.
     *
     * @var array<int, string>
     */
    public const DOC_TYPES = [
        'invoice',
        'order',
        'purchase-order',
        'delivery',
        'quote',
    ];

    /**
     * The 5 shipped template slugs, in picker display order.
     *
     * @var array<int, string>
     */
    public const SLUGS = [
        'modern',
        'classic',
        'bold',
        'compact',
        'professional',
    ];

    /**
     * Public-relative directory holding the picker thumbnails — where
     * `vendor:publish --tag=assets` lands the artwork shipped in
     * `resources/assets/img/pdf-templates`.
     */
    public const THUMBNAIL_DIR = 'vendor/laravel-crm/img/pdf-templates';

    /**
     * The pre-template view each doc type used to render through, keyed by
     * doc type. These still ship, and `classic` is a thin @include of them,
     * but nothing resolves to them by default any more.
     *
     * They matter because a host that customised its PDFs before the picker
     * existed did it by publishing and editing exactly these files — see
     * publishedOverrideView().
     *
     * @var array<string, string>
     */
    public const LEGACY_VIEWS = [
        'invoice' => 'invoices.pdf',
        'order' => 'orders.pdf',
        'purchase-order' => 'purchase-orders.pdf',
        'delivery' => 'deliveries.pdf',
        'quote' => 'quotes.pdf',
    ];

    /**
     * Memoised publishedOverrideView() answers, keyed by doc type.
     *
     * The probe hashes two files, which is nothing next to rendering a PDF,
     * but a document with many line items resolves its view more than once.
     *
     * @var array<string, string|null>
     */
    protected static array $overrides = [];

    /**
     * The default template slug — always resolvable via `viewFor(...)` for
     * every doc type, and used as the fallback when a caller passes an
     * unknown slug.
     */
    public static function defaultSlug(): string
    {
        return self::DEFAULT_SLUG;
    }

    /**
     * Metadata for every shipped template.
     *
     * Keyed by slug; each entry carries `slug`, `label`, `description`, and
     * a `thumbnail` path (relative to the host's public/ directory) suitable
     * for rendering a preview picker.
     *
     * @return array<string, array{slug:string, label:string, description:string, thumbnail:string}>
     */
    public static function all(): array
    {
        $entries = [];

        foreach (self::SLUGS as $slug) {
            $entries[$slug] = [
                'slug' => $slug,
                'label' => ucfirst(__('laravel-crm::lang.pdf_template_'.$slug)),
                'description' => __('laravel-crm::lang.pdf_template_'.$slug.'_description'),
                'thumbnail' => self::THUMBNAIL_DIR.'/'.$slug.'.svg',
            ];
        }

        return $entries;
    }

    /**
     * Resolve the on-disk path of a template's picker thumbnail, or null
     * when the slug is unknown / the file is missing.
     *
     * Checks the host's published copy first so an app that has overridden
     * the shipped artwork keeps its override, then falls back to the copy
     * inside the package. The fallback is what makes the picker work on a
     * host whose `vendor:publish --tag=assets` predates this artwork — the
     * thumbnails ship with the package, so requiring a re-publish just to
     * see them silently degraded the picker to text-only placeholders.
     *
     * The packaged copy lives under `resources/assets`, not under `public/`:
     * `public/vendor/laravel-crm` is the Vite output directory and
     * `emptyOutDir: true` deletes anything in it that the build did not
     * produce, which is how 2.4.0 shipped with no thumbnail artwork at all
     * and left this fallback with nothing to fall back to. `resources/assets`
     * publishes to the same destination, so THUMBNAIL_DIR is unaffected.
     */
    public static function thumbnailFile(string $slug): ?string
    {
        if (! in_array($slug, self::SLUGS, true)) {
            return null;
        }

        $relative = self::THUMBNAIL_DIR.'/'.$slug.'.svg';

        $candidates = [
            public_path($relative),
            __DIR__.'/../../resources/assets/img/pdf-templates/'.$slug.'.svg',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Resolve the Blade view path for `$docType` rendered with template
     * `$slug`. Unknown slugs fall back to `defaultSlug()`.
     *
     * @param  string  $docType  one of self::DOC_TYPES
     * @param  string  $slug  one of the keys returned by all()
     */
    public static function viewFor(string $docType, string $slug): string
    {
        $resolved = array_key_exists($slug, self::all()) ? $slug : self::defaultSlug();

        return 'laravel-crm::pdfs.'.$resolved.'.'.$docType;
    }

    /**
     * The settings key holding the admin-chosen default template for
     * `$docType`. Hyphenated doc types keep their hyphen
     * (`pdf_template_purchase-order`) — the convention the settings
     * screen writes with.
     */
    public static function settingKey(string $docType): string
    {
        return 'pdf_template_'.$docType;
    }

    /**
     * The admin-chosen default template slug for `$docType`, falling back
     * to `defaultSlug()` when nothing has been saved in settings (or the
     * saved value references a template that no longer ships).
     */
    public static function defaultFor(string $docType): string
    {
        $slug = app('laravel-crm.settings')->get(self::settingKey($docType), self::defaultSlug());

        return array_key_exists($slug, self::all()) ? $slug : self::defaultSlug();
    }

    /**
     * Narrow an untrusted slug down to one this package actually ships,
     * or null. Used on the write path so a record never persists a
     * template that can't be rendered.
     *
     * A null return is meaningful rather than a failure: it is how a
     * record says "follow the Settings → Templates default".
     *
     * @param  mixed  $slug
     */
    public static function sanitize($slug): ?string
    {
        return (is_string($slug) && array_key_exists($slug, self::all())) ? $slug : null;
    }

    /**
     * The `pdf_template` to persist when updating a record.
     *
     * Only the document forms carry the picker. Every other writer — the
     * REST API, the legacy controllers, the multi-purchase-order split —
     * submits nothing at all for this field, and must leave whatever the
     * record already carries alone rather than silently clearing it.
     *
     * A form that submits the blank option sends '' (not null), which is
     * an explicit request to go back to tracking the settings default, so
     * that clears the column.
     *
     * @param  mixed  $submitted  the submitted value, or null when the writer has no picker
     */
    public static function resolveUpdate($submitted, ?string $current): ?string
    {
        return $submitted === null ? $current : self::sanitize($submitted);
    }

    /**
     * Resolve the template slug for a single record: the template picked
     * on the record itself when it has one, otherwise the settings
     * default for `$docType`. Records created before the per-record
     * picker existed carry a null `pdf_template` and so keep tracking
     * whatever settings says.
     *
     * @param  object|null  $model  an Invoice/Order/PurchaseOrder/Delivery/Quote
     */
    public static function slugFor(string $docType, $model = null): string
    {
        $slug = $model->pdf_template ?? null;

        if ($slug && array_key_exists($slug, self::all())) {
            return $slug;
        }

        return self::defaultFor($docType);
    }

    /**
     * Resolve the Blade view path for a single record — the per-record
     * template when set, else the settings default for `$docType`, else a
     * PDF view the host has customised by hand.
     *
     * That last fallback is the whole point of this method existing rather
     * than callers composing viewFor(slugFor(...)) themselves: before the
     * picker, the only way to restyle a PDF was to publish
     * `resources/views/vendor/laravel-crm/invoices/pdf.blade.php` and edit it.
     * Switching the default to `modern` would have silently thrown that work
     * away on upgrade, for a host that never asked for a new template and has
     * no reason to look for a picker.
     *
     * An explicit choice — on the record or in Settings → Templates — always
     * wins, so opting in to a shipped template is one click and stays sticky.
     *
     * @param  object|null  $model  an Invoice/Order/PurchaseOrder/Delivery/Quote
     */
    public static function viewForModel(string $docType, $model = null): string
    {
        if ($chosen = self::explicitSlugFor($docType, $model)) {
            return self::viewFor($docType, $chosen);
        }

        return self::publishedOverrideView($docType)
            ?? self::viewFor($docType, self::defaultSlug());
    }

    /**
     * The template explicitly chosen for this document, or null when nobody
     * has chosen one and the default is merely being inherited.
     *
     * Distinguishing the two is what lets a customised published view act as
     * the default without overriding a real choice. `slugFor()` cannot answer
     * this — it collapses "unset" into DEFAULT_SLUG by design.
     *
     * @param  object|null  $model
     */
    protected static function explicitSlugFor(string $docType, $model = null): ?string
    {
        if ($slug = self::sanitize($model->pdf_template ?? null)) {
            return $slug;
        }

        return self::sanitize(app('laravel-crm.settings')->get(self::settingKey($docType)));
    }

    /**
     * The legacy view for `$docType`, but only when the host has published it
     * *and changed it*.
     *
     * The content comparison matters: `vendor:publish --tag=views` copies the
     * entire views directory, so the presence of the file says nothing on its
     * own — a host that published last week has a byte-identical copy it never
     * intended as an override, and honouring that would pin them to the old
     * layout forever. A hash mismatch is the only reliable signal that someone
     * edited it.
     */
    public static function publishedOverrideView(string $docType): ?string
    {
        if (array_key_exists($docType, self::$overrides)) {
            return self::$overrides[$docType];
        }

        return self::$overrides[$docType] = self::probePublishedOverride($docType);
    }

    /**
     * Drop the memoised override probe. For tests, and for anything that
     * writes to the published view directory within a single process.
     */
    public static function forgetPublishedOverrides(): void
    {
        self::$overrides = [];
    }

    protected static function probePublishedOverride(string $docType): ?string
    {
        $view = self::LEGACY_VIEWS[$docType] ?? null;

        if ($view === null) {
            return null;
        }

        $relative = str_replace('.', '/', $view).'.blade.php';

        $published = resource_path('views/vendor/laravel-crm/'.$relative);
        $shipped = __DIR__.'/../../resources/views/'.$relative;

        if (! is_file($published) || ! is_readable($published) || ! is_file($shipped)) {
            return null;
        }

        if (md5_file($published) === md5_file($shipped)) {
            return null;
        }

        return 'laravel-crm::'.$view;
    }

    /**
     * Template list shaped for a `<x-mary-select>` — `id`/`name` pairs in
     * the registry's display order.
     *
     * The blank "follow the settings default" choice is not in here; it is
     * rendered as the select's placeholder option, labelled by
     * `defaultOptionLabel()`.
     *
     * @return array<int, array{id:string, name:string}>
     */
    public static function options(): array
    {
        return array_values(array_map(fn (array $template) => [
            'id' => $template['slug'],
            'name' => $template['label'],
        ], self::all()));
    }

    /**
     * Label for the picker's blank option — the one that leaves a record
     * tracking Settings → Templates. Names the template that choice
     * currently resolves to, e.g. "Default (Bold)", so the picker still
     * tells you what you are going to get.
     */
    public static function defaultOptionLabel(string $docType): string
    {
        return ucfirst(__('laravel-crm::lang.pdf_template_use_default', [
            'template' => self::all()[self::defaultFor($docType)]['label'],
        ]));
    }
}
