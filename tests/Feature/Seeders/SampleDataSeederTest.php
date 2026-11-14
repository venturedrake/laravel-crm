<?php

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmPipelineTablesSeeder;
use VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmSampleDataSeeder;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\UserInvitation;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;
use VentureDrake\LaravelCrm\Support\PdfTemplateRegistry;
use VentureDrake\LaravelCrm\Support\Quantity;

use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\lineAmount;
use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\subTotal;
use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\tax;
use function VentureDrake\LaravelCrm\Http\Helpers\CheckAmount\total;

/**
 * Execution coverage for `laravelcrm:sample-data`.
 *
 * The seeder is 4,000+ lines of hand-written Model::create() across ~45 tables
 * and nothing else in the suite ever runs it, so a model change three commits
 * away can silently break the demo dataset. These two tests run it end to end
 * and assert the invariants that a real install depends on: money stored as
 * integer cents, decimal quantities that survive the copy from quote to order
 * to invoice, every document sitting in a stage of its own pipeline, and the
 * 2.4.0 surfaces (task start_at, pdf_template, user invitations) actually
 * being populated.
 *
 * Every assertion is a universally-quantified invariant paired with a
 * non-vacuity guard, so the seeder's randomness cannot make them flaky.
 */

/**
 * A 2%-scale seeder. $scale is read in exactly two places -- inside scaled()
 * and in the banner label -- so overriding scaled() is enough to shrink the
 * dataset without touching the shipped CLI, and max(1, ...) keeps every phase
 * producing at least one row of everything.
 */
class TinySampleDataSeeder extends LaravelCrmSampleDataSeeder
{
    public static float $factor = 0.02;

    protected function scaled(int $value): int
    {
        return $value <= 0 ? 0 : max(1, (int) round($value * self::$factor));
    }
}

// Guarded so a full-suite run alongside sibling files declaring the same
// helpers does not hit a "cannot redeclare" fatal.
if (! function_exists('ensureSampleDataProbabilitiesTable')) {
    function ensureSampleDataProbabilitiesTable(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');

        if (! Schema::hasTable($prefix.'pipeline_stage_probabilities')) {
            Schema::create($prefix.'pipeline_stage_probabilities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('name');
                $table->integer('percent')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable($prefix.'pipeline_stages')
            && ! Schema::hasColumn($prefix.'pipeline_stages', 'pipeline_stage_probability_id')) {
            Schema::table($prefix.'pipeline_stages', function (Blueprint $table) {
                $table->unsignedBigInteger('pipeline_stage_probability_id')->nullable();
            });
        }
    }
}

if (! function_exists('ensureSampleDataRolesTable')) {
    /**
     * seedUsersAndTeams() calls Role::crmNotOwner()->get() unconditionally and
     * throws without this table. Leaving it empty is fine -- the test User stub
     * has no syncRoles(), so the seeder's method_exists guard skips assignment
     * and invitations fall back to a null role_id.
     */
    function ensureSampleDataRolesTable(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('description')->nullable();
                $table->boolean('crm_role')->default(0);
                $table->timestamps();
            });
        }
    }
}

if (! function_exists('runSampleDataSeeder')) {
    /**
     * Drive the seeder with a stub console.
     *
     * A real $this->artisan() run would have to match the 300-character
     * confirmation question --fresh asks, so the seeder is invoked directly
     * with a concrete Command whose output is swallowed. createProgressBar()
     * needs a real OutputStyle, hence not a plain NullOutput.
     */
    function runSampleDataSeeder(bool $fresh = false): void
    {
        $command = new Command;
        $command->setLaravel(app());
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

        (new TinySampleDataSeeder)
            ->setContainer(app())
            ->setCommand($command)
            ->run($fresh);
    }
}

beforeEach(function () {
    ensureSampleDataProbabilitiesTable();
    ensureSampleDataRolesTable();

    Setting::updateOrCreate(['name' => 'currency'], ['value' => 'USD']);

    (new LaravelCrmPipelineTablesSeeder)->run();

    // The seeder takes the first user in the table as the record owner.
    $this->actingAsUser();

    // seedLabels() returns early on an empty label set. LaravelCrmTablesSeeder
    // would supply these but needs Spatie's permission tables, which TestSchema
    // does not ship, so insert them by hand.
    foreach ([['Hot', '#ff0000'], ['Cold', '#0000ff'], ['VIP', '#00ff00']] as [$name, $hex]) {
        Label::create(['name' => $name, 'hex' => $hex]);
    }

    // Narrows the spread between runs; it does not make the seeder
    // reproducible -- Str::random() and Uuid::uuid4() draw from the CSPRNG, and
    // several counts key off row ids. Nothing below depends on a fixed dataset.
    mt_srand(20260809);
});

it('seeds a coherent 2.4.0 dataset', function () {
    runSampleDataSeeder();

    // --- Non-vacuity: every phase produced rows -------------------------------

    foreach ([
        Organization::class, Person::class, Lead::class, Deal::class, Quote::class,
        Order::class, Invoice::class, Delivery::class, PurchaseOrder::class,
        Task::class, Note::class, Call::class, Activity::class, FieldValue::class,
    ] as $model) {
        expect($model::count())->toBeGreaterThan(0, class_basename($model).' rows were seeded');
    }

    // --- Pipeline integrity ---------------------------------------------------

    foreach ([
        Lead::class, Deal::class, Quote::class, Order::class,
        Invoice::class, PurchaseOrder::class, Delivery::class,
    ] as $model) {
        $label = class_basename($model);

        expect($model::whereNull('pipeline_stage_id')->count())
            ->toBe(0, "every {$label} sits in a pipeline stage");

        $ownStageIds = Pipeline::where('model', $model)
            ->first()
            ->pipelineStages()
            ->pluck('id')
            ->all();

        expect($ownStageIds)->not->toBeEmpty();
        expect($model::whereNotIn('pipeline_stage_id', $ownStageIds)->count())
            ->toBe(0, "every {$label} stage belongs to the {$label} pipeline");
    }

    // --- Money is stored as integer cents -------------------------------------

    // Cross-check line prices against the catalogue price they were derived
    // from: generateLineItems() uses unit_price / 100, optionally x 0.9. A
    // regression to storing dollars would drop these ~100x.
    $sampled = QuoteProduct::whereNotNull('product_id')->take(25)->get();
    expect($sampled)->not->toBeEmpty();

    foreach ($sampled as $line) {
        $unitPrice = ProductPrice::where('product_id', $line->product_id)->value('unit_price');

        if ($unitPrice === null) {
            continue;
        }

        expect((int) $line->price)
            ->toBeGreaterThanOrEqual((int) round($unitPrice * 0.89))
            ->toBeLessThanOrEqual((int) $unitPrice);
    }

    // The CheckAmount helpers are what the UI uses to badge a document as
    // broken. They only understand Quotes and Orders (getItems() returns null
    // for anything else), and they exercise the money mutators, the per-line
    // rounding and the fractional quantities in one pass.
    foreach (Quote::with('quoteProducts')->get() as $quote) {
        expect(subTotal($quote))->toBeTrue("quote {$quote->id} subtotal");
        expect(tax($quote))->toBeTrue("quote {$quote->id} tax");
        expect(total($quote))->toBeTrue("quote {$quote->id} total");

        foreach ($quote->quoteProducts as $line) {
            expect(lineAmount($line))->toBeTrue("quote {$quote->id} line {$line->id}");
        }
    }

    foreach (Order::with('orderProducts')->get() as $order) {
        expect(subTotal($order))->toBeTrue("order {$order->id} subtotal");
        expect(tax($order))->toBeTrue("order {$order->id} tax");
        expect(total($order))->toBeTrue("order {$order->id} total");

        foreach ($order->orderProducts as $line) {
            expect(lineAmount($line))->toBeTrue("order {$order->id} line {$line->id}");
        }
    }

    // --- Fractional quantities round-trip and propagate ------------------------

    $fractional = QuoteProduct::whereRaw('quantity <> CAST(quantity AS INTEGER)')->get();
    expect($fractional)->not->toBeEmpty('some line items are sold by weight or volume');

    foreach ($fractional as $line) {
        expect($line->quantity)->toBeFloat();
        expect($line->quantity)->toBe(Quantity::round($line->quantity));
    }

    // Quantities are looked up by key rather than through a relation because
    // OrderProduct and InvoiceLine do not declare belongsTo's for their parents.
    $quoteQuantities = QuoteProduct::pluck('quantity', 'id');
    $orderQuantities = OrderProduct::pluck('quantity', 'id');

    $childChecks = [
        ['from quote to order', OrderProduct::whereNotNull('quote_product_id')->get(['id', 'quantity', 'quote_product_id']), 'quote_product_id', $quoteQuantities],
        ['from order to invoice', InvoiceLine::whereNotNull('order_product_id')->get(['id', 'quantity', 'order_product_id']), 'order_product_id', $orderQuantities],
        ['from order to delivery', DeliveryProduct::whereNotNull('order_product_id')->get(['id', 'quantity', 'order_product_id']), 'order_product_id', $orderQuantities],
    ];

    foreach ($childChecks as [$label, $children, $key, $parents]) {
        expect($children)->not->toBeEmpty("quantities propagate {$label}");

        foreach ($children as $child) {
            expect(Quantity::equals($child->quantity, $parents[$child->{$key}]))
                ->toBeTrue("line {$child->id} kept its quantity {$label}");
        }
    }

    // --- Completion helpers agree with how the data was built ------------------

    // The seeder copies quantities across in full, so these hold by construction
    // -- what they actually pin is Quantity::isPositive() tolerating float dust.
    $ordered = Quote::has('orders')->get();
    expect($ordered)->not->toBeEmpty();
    foreach ($ordered as $quote) {
        expect($quote->orderComplete())->toBeTrue("quote {$quote->id} is fully ordered");
    }

    $invoiced = Order::has('invoices')->get();
    expect($invoiced)->not->toBeEmpty();
    foreach ($invoiced as $order) {
        expect($order->invoiceComplete())->toBeTrue("order {$order->id} is fully invoiced");
    }

    $delivered = Order::has('deliveries')->get();
    expect($delivered)->not->toBeEmpty();
    foreach ($delivered as $order) {
        expect($order->deliveryComplete())->toBeTrue("order {$order->id} is fully delivered");
    }

    // The negative direction, so the helpers are not just returning true.
    if ($undelivered = Order::doesntHave('deliveries')->has('orderProducts')->first()) {
        expect($undelivered->deliveryComplete())->toBeFalse();
    }

    // --- Custom field values ---------------------------------------------------

    // HasCrmFields::booted() creates one FieldValue per applicable field on
    // every entity; setFieldValue() then only UPDATEs those rows, so it would
    // silently no-op if the auto-creation ever stopped.
    $leadFieldCount = Field::whereHas('fieldModels', fn ($q) => $q->where('model', Lead::class))->count();
    expect($leadFieldCount)->toBeGreaterThan(0);

    expect(FieldValue::where('field_valueable_type', Lead::class)->count())
        ->toBe($leadFieldCount * Lead::count());

    expect(FieldValue::whereNotNull('value')->count())->toBeGreaterThan(0);

    // --- 2.4.0 surfaces --------------------------------------------------------

    expect(Task::whereNotNull('start_at')->count())->toBeGreaterThan(0);
    expect(Task::whereNull('start_at')->count())->toBeGreaterThan(0);
    expect(Task::whereColumn('start_at', '>', 'due_at')->count())
        ->toBe(0, 'no task starts after it is due');

    foreach ([Quote::class, Order::class, Invoice::class, Delivery::class, PurchaseOrder::class] as $model) {
        $label = class_basename($model);

        expect($model::whereNotNull('pdf_template')->count())
            ->toBeGreaterThan(0, "some {$label}s pick a PDF template");

        // A null pdf_template is the "follow the Settings default" state.
        expect($model::whereNull('pdf_template')->count())
            ->toBeGreaterThan(0, "some {$label}s track the Settings default");

        expect($model::whereNotNull('pdf_template')
            ->whereNotIn('pdf_template', PdfTemplateRegistry::SLUGS)
            ->count())->toBe(0, "every {$label} template is one this package ships");
    }

    $invitations = UserInvitation::all();
    expect($invitations->where('accepted_at', '!=', null))->not->toBeEmpty();
    expect($invitations->filter->isPending())->not->toBeEmpty();
    expect($invitations->filter->isExpired())->not->toBeEmpty();
    expect($invitations->whereNull('code'))->toBeEmpty();

    // --- Campaign placeholders use the syntax the renderers support ------------

    // Campaign bodies are rendered with `{key}`; seeded `{{key}}` used to
    // preview as "Hello {Jane},", and keys outside the supported list are
    // never substituted at all.
    $placeholderChecks = [
        [EmailTemplate::class, ['subject', 'preview_text', 'body'], EmailCampaignMessage::availablePlaceholders()],
        [EmailCampaign::class, ['subject', 'preview_text', 'body'], EmailCampaignMessage::availablePlaceholders()],
        [SmsTemplate::class, ['body'], SmsCampaignMessage::availablePlaceholders()],
        [SmsCampaign::class, ['body'], SmsCampaignMessage::availablePlaceholders()],
    ];

    foreach ($placeholderChecks as [$model, $columns, $supported]) {
        $label = class_basename($model);
        $rows = $model::all();
        expect($rows)->not->toBeEmpty("{$label} rows were seeded");

        $placeholdersSeen = 0;

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $text = (string) $row->{$column};

                expect(str_contains($text, '{{'))
                    ->toBeFalse("{$label} {$row->id} {$column} uses single-brace placeholders");

                preg_match_all('/\{(\w+)\}/', $text, $matches);
                $placeholdersSeen += count($matches[1]);

                foreach ($matches[1] as $key) {
                    expect(array_key_exists($key, $supported))
                        ->toBeTrue("{$label} {$row->id} {$column} uses supported placeholder {{$key}}");
                }
            }
        }

        expect($placeholdersSeen)->toBeGreaterThan(0, "{$label} rows use placeholders");
    }

    $newsletter = EmailTemplate::where('name', 'Monthly Newsletter')->first();
    expect($newsletter)->not->toBeNull();
    expect(EmailCampaignMessage::renderPreview($newsletter->body, $newsletter->preview_text ?? ''))
        ->toContain('Hello Jane,')
        ->not->toContain('{Jane}');
    expect(EmailCampaignMessage::renderPreviewSubject($newsletter->subject))
        ->toBe('Your Monthly Update from '.config('app.name'));
});

it('replaces rather than accumulates on a second --fresh run', function () {
    runSampleDataSeeder(fresh: true);

    $prefix = config('laravel-crm.db_table_prefix');

    // Only tables whose size is scaled() of a constant -- never a random draw.
    $deterministic = [
        $prefix.'organizations',
        $prefix.'people',
        $prefix.'leads',
        $prefix.'lead_sources',
        $prefix.'products',
        $prefix.'product_categories',
        $prefix.'fields',
        $prefix.'field_groups',
        $prefix.'field_models',
        $prefix.'field_options',
        $prefix.'feature_statuses',
        $prefix.'features',
        $prefix.'chat_widgets',
        $prefix.'monitors',
        $prefix.'user_invitations',
        'users',
        'crm_teams',
    ];

    $before = collect($deterministic)
        ->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);

    expect($before->filter(fn ($count) => $count === 0))
        ->toBeEmpty('every deterministic table was populated by the first run');

    runSampleDataSeeder(fresh: true);

    foreach ($before as $table => $count) {
        // Counts, not identities: SQLite's truncate() is a plain DELETE FROM
        // and bigIncrements without AUTOINCREMENT reuses rowids.
        expect(DB::table($table)->count())->toBe($count, "{$table} was replaced, not doubled");
    }

    // The genuinely random tables only get a non-vacuity check.
    foreach ([Deal::class, Quote::class, Order::class, Invoice::class, Activity::class] as $model) {
        expect($model::count())->toBeGreaterThan(0, class_basename($model).' survived the re-run');
    }

    // No invitation points at a user the fresh run hard-deleted.
    expect(UserInvitation::whereNotNull('invited_by')
        ->whereNotIn('invited_by', DB::table('users')->pluck('id'))
        ->count())->toBe(0);
});
