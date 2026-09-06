<?php

use Barryvdh\DomPDF\ServiceProvider as DomPdfServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;

beforeEach(function () {
    // The Barryvdh DomPDF ServiceProvider isn't in the test suite's
    // package-provider list; register it here so `Pdf::loadView(...)`
    // resolves the `dompdf.wrapper` container binding at request time.
    $this->app->register(DomPdfServiceProvider::class);

    $this->actingAsUser(['crm_access' => 1]);
    // Grant the settings-permission gate so the middleware chain lets the
    // request reach the controller. Matches the pattern from
    // Livewire/Users/UserIndexTabsTest and other permission-gated tests
    // in this suite.
    Gate::before(fn () => true);
});

test('preview route is registered under the settings-permission middleware group', function () {
    $route = Route::getRoutes()->getByName('laravel-crm.settings.templates.preview');

    expect($route)->not->toBeNull();
    expect($route->uri())->toContain('settings/templates/preview/{docType}/{slug}');
    expect($route->methods())->toContain('GET');
    expect($route->middleware())->toContain('auth.laravel-crm');
    expect($route->middleware())->toContain('can:update,VentureDrake\LaravelCrm\Models\Setting');
});

test('preview streams a valid PDF inline for every (docType x slug) pair', function () {
    $docTypes = PdfTemplateRegistry::DOC_TYPES;
    $slugs = array_keys(PdfTemplateRegistry::all());

    // 5 doc types x 5 template slugs = 25 valid combinations declared
    // by the registry. The delivery blades read from `deliveryProducts()`
    // as a live query builder (not the pre-loaded relation), so exercising
    // them requires the `crm_delivery_products` table — absent from the
    // core TestSchema. Skip delivery in test environments that lack it;
    // production hosts (which ship the full schema) exercise all 25.
    expect(count($docTypes))->toBe(5);
    expect(count($slugs))->toBe(5);

    $renderable = Schema::hasTable('crm_delivery_products')
        ? $docTypes
        : array_values(array_filter($docTypes, fn ($t) => $t !== 'delivery'));

    foreach ($renderable as $docType) {
        foreach ($slugs as $slug) {
            $response = $this->get(route('laravel-crm.settings.templates.preview', [
                'docType' => $docType,
                'slug' => $slug,
            ]));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');

            // Every PDF must begin with the standard `%PDF-` magic bytes.
            expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
        }
    }
});

test('unknown docType returns 404', function () {
    $response = $this->get(route('laravel-crm.settings.templates.preview', [
        'docType' => 'nonexistent-doc-type',
        'slug' => 'modern',
    ]));

    $response->assertNotFound();
});

test('unknown slug returns 404', function () {
    $response = $this->get(route('laravel-crm.settings.templates.preview', [
        'docType' => 'invoice',
        'slug' => 'nonexistent-slug',
    ]));

    $response->assertNotFound();
});

test('preview produces no database writes across every doc type', function () {
    $counts = [
        'invoices' => Invoice::query()->count(),
        'orders' => Order::query()->count(),
        'purchase_orders' => PurchaseOrder::query()->count(),
        'quotes' => Quote::query()->count(),
        'people' => Person::query()->count(),
        'organizations' => Organization::query()->count(),
        'addresses' => Address::query()->count(),
    ];

    $renderable = Schema::hasTable('crm_delivery_products')
        ? PdfTemplateRegistry::DOC_TYPES
        : array_values(array_filter(PdfTemplateRegistry::DOC_TYPES, fn ($t) => $t !== 'delivery'));

    foreach ($renderable as $docType) {
        $response = $this->get(route('laravel-crm.settings.templates.preview', [
            'docType' => $docType,
            'slug' => PdfTemplateRegistry::defaultSlug(),
        ]));

        $response->assertOk();
    }

    expect(Invoice::query()->count())->toBe($counts['invoices']);
    expect(Order::query()->count())->toBe($counts['orders']);
    expect(PurchaseOrder::query()->count())->toBe($counts['purchase_orders']);
    expect(Quote::query()->count())->toBe($counts['quotes']);
    expect(Person::query()->count())->toBe($counts['people']);
    expect(Organization::query()->count())->toBe($counts['organizations']);
    expect(Address::query()->count())->toBe($counts['addresses']);

    if (Schema::hasTable('crm_deliveries')) {
        expect(Delivery::query()->count())->toBe(0);
    }
});

test('preview works on a brand-new install with zero real records', function () {
    // No fixture seeding — the underlying tables are empty. This test
    // confirms PdfSampleData's in-memory model fabrication is enough to
    // render every doc type without needing any DB rows.
    expect(Invoice::query()->count())->toBe(0);
    expect(Person::query()->count())->toBe(0);
    expect(Organization::query()->count())->toBe(0);

    $renderable = Schema::hasTable('crm_delivery_products')
        ? PdfTemplateRegistry::DOC_TYPES
        : array_values(array_filter(PdfTemplateRegistry::DOC_TYPES, fn ($t) => $t !== 'delivery'));

    foreach ($renderable as $docType) {
        $response = $this->get(route('laravel-crm.settings.templates.preview', [
            'docType' => $docType,
            'slug' => PdfTemplateRegistry::defaultSlug(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
});

test('thumbnail route is registered under the settings-permission middleware group', function () {
    $route = Route::getRoutes()->getByName('laravel-crm.settings.templates.thumbnail');

    expect($route)->not->toBeNull();
    expect($route->uri())->toContain('settings/templates/thumbnail/{slug}');
    expect($route->methods())->toContain('GET');
    expect($route->middleware())->toContain('auth.laravel-crm');
    expect($route->middleware())->toContain('can:update,VentureDrake\LaravelCrm\Models\Setting');
});

test('thumbnail serves SVG artwork for every slug', function () {
    foreach (PdfTemplateRegistry::SLUGS as $slug) {
        $response = $this->get(route('laravel-crm.settings.templates.thumbnail', ['slug' => $slug]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        expect($response->baseResponse->getFile()->getContent())->toContain('<svg');
    }
});

test('thumbnail falls back to the packaged artwork when the host has not published assets', function () {
    // The regression this guards: a host whose `vendor:publish --tag=assets`
    // predates the thumbnails has no `public/vendor/laravel-crm/img/
    // pdf-templates` directory, and the picker silently degraded to
    // text-only placeholders. Point public_path() at an empty directory to
    // reproduce that host exactly.
    $this->app->usePublicPath(sys_get_temp_dir().'/laravel-crm-no-published-assets');

    foreach (PdfTemplateRegistry::SLUGS as $slug) {
        expect(file_exists(public_path(PdfTemplateRegistry::THUMBNAIL_DIR.'/'.$slug.'.svg')))->toBeFalse();

        $path = PdfTemplateRegistry::thumbnailFile($slug);

        expect($path)->not->toBeNull();
        expect(is_file($path))->toBeTrue();
        expect(file_get_contents($path))->toContain('<svg');

        // The packaged artwork must not live in the Vite output directory:
        // `emptyOutDir: true` wipes it on every `npm run build`, which is how
        // 2.4.0 shipped with no thumbnails at all and this fallback resolved
        // to null on every host.
        expect($path)->not->toContain('public/vendor/laravel-crm');

        $response = $this->get(route('laravel-crm.settings.templates.thumbnail', ['slug' => $slug]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }
});

test('thumbnail prefers a host override over the packaged artwork', function () {
    $publicPath = sys_get_temp_dir().'/laravel-crm-override-assets';
    $dir = $publicPath.'/'.PdfTemplateRegistry::THUMBNAIL_DIR;

    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $override = $dir.'/modern.svg';
    file_put_contents($override, '<svg xmlns="http://www.w3.org/2000/svg"><!-- host override --></svg>');

    $this->app->usePublicPath($publicPath);

    expect(PdfTemplateRegistry::thumbnailFile('modern'))->toBe($override);

    $response = $this->get(route('laravel-crm.settings.templates.thumbnail', ['slug' => 'modern']));

    $response->assertOk();
    expect($response->baseResponse->getFile()->getContent())->toContain('host override');

    unlink($override);
});

test('thumbnail 404s for an unknown slug', function () {
    $response = $this->get(route('laravel-crm.settings.templates.thumbnail', ['slug' => 'not-a-template']));

    $response->assertNotFound();
});
