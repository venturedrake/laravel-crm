<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\ContactType;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Industry;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\OrganisationType;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrm\Models\Team;

class LaravelCrmSampleDataSeeder extends Seeder
{
    /** @var \Faker\Generator */
    protected $faker;

    /** @var \Illuminate\Support\Collection */
    protected $userIds;

    /** @var \Illuminate\Support\Collection */
    protected $teamIds;

    /** @var string */
    protected $dateFormat = 'Y/m/d';

    /** @var string */
    protected $dateTimeFormat = 'Y/m/d H:i';

    /**
     * Cached product pricing keyed by product id:
     *   [productId => ['unit_price' => dollars, 'cost' => dollars, 'tax_rate' => decimal]]
     *
     * Populated by seedProductCatalogue() and re-used by every line-item
     * generator so totals are deterministic & reproducible.
     *
     * @var array<int, array<string, float|int>>
     */
    protected $productPricing = [];

    /** @var \Illuminate\Support\Collection|null */
    protected $labelIds;

    /** @var \Illuminate\Support\Collection|null */
    protected $industryIds;

    /** @var \Illuminate\Support\Collection|null */
    protected $organisationTypeIds;

    /** @var \Illuminate\Support\Collection|null */
    protected $leadSourceIds;

    /** @var int|null */
    protected $defaultTaxRateId;

    /**
     * Pipeline stage IDs (from LaravelCrmPipelineTablesSeeder):
     *
     * Lead:           1 New, 2 Appointment Scheduled, 3 Qualified To Buy,
     *                 4 Presentation Scheduled, 5 Decision Maker Bought-In,
     *                 6 Contract Sent, 7 Closed Won, 8 Closed Lost
     * Deal:           9 Draft, 10 Pending, 11 Closed Won, 12 Closed Lost
     * Quote:         13 Draft, 14 Sent, 15 Accepted, 16 Rejected, 17 Ordered
     * Order:         18 Draft, 19 Open, 20 Invoiced, 21 Delivered, 22 Completed
     * Invoice:       23 Draft, 24 Awaiting Approval, 25 Awaiting Payment, 26 Paid
     * Delivery:      27 Draft, 28 Packed, 29 Sent, 30 Delivered
     * Purchase Order:31 Draft, 32 Awaiting Approval, 33 Approved, 34 Paid
     *
     * Address types (from LaravelCrmTablesSeeder):
     *   1 Current, 2 Previous, 3 Postal, 4 Business, 5 Billing, 6 Shipping
     *
     * IMPORTANT – dollar vs cents:
     *   Quote / Order / Invoice / PurchaseOrder mutators (setSubtotalAttribute,
     *   setTaxAttribute, setTotalAttribute, etc.) ALL multiply the passed value
     *   by 100 before storing.  Therefore always pass DOLLAR values to these
     *   models; the mutator will store cents automatically.
     */

    public function run()
    {
        $this->faker = \Faker\Factory::create();

        $this->dateFormat     = Deal::dateFormat();
        $this->dateTimeFormat = $this->dateFormat.' H:i';

        DB::transaction(function () {
            $this->step('Users', fn () => $this->seedUsers());
            $this->step('Teams', fn () => $this->seedTeams());
            $this->step('Tax rates', fn () => $this->seedTaxRates());
            $this->step('Lookups (lead sources, industries)', fn () => $this->seedLookups());
            $this->step('Product catalogue', fn () => $this->seedProductCatalogue());
            $this->step('Custom fields', fn () => $this->seedCustomFields());
            $this->step('Organisations & people', fn () => $this->seedOrganisationsAndPeople());
            $this->step('Clients', fn () => $this->seedClients());
            $this->step('Contacts', fn () => $this->seedContacts());
            $this->step('Leads & deals', fn () => $this->seedLeadsAndDeals());
            $this->step('Deal products', fn () => $this->seedDealProducts());
            $this->step('Quotes', fn () => $this->seedQuotes());
            $this->step('Orders', fn () => $this->seedOrders());
            $this->step('Invoices', fn () => $this->seedInvoices());
            $this->step('Deliveries', fn () => $this->seedDeliveries());
            $this->step('Purchase orders', fn () => $this->seedPurchaseOrders());
            $this->step(
                'Activities (tasks / notes / calls / meetings / lunches)',
                fn () => $this->seedActivities()
            );
            $this->step('Custom field values', fn () => $this->applyCustomFieldValues());
        });

        $this->writeln('');
        $this->writeln('Sample data summary:');
        $this->writeln(sprintf('  Organisations:   %d', Organisation::count()));
        $this->writeln(sprintf('  People:          %d', Person::count()));
        $this->writeln(sprintf('  Contacts:        %d', Contact::count()));
        $this->writeln(sprintf('  Clients:         %d', Client::count()));
        $this->writeln(sprintf('  Industries:      %d', Industry::count()));
        $this->writeln(sprintf('  Lead sources:    %d', LeadSource::count()));
        $this->writeln(sprintf('  Tax rates:       %d', TaxRate::count()));
        $this->writeln(sprintf('  Products:        %d', Product::count()));
        $this->writeln(sprintf('  Product prices:  %d', ProductPrice::count()));
        $this->writeln(sprintf('  Leads:           %d', Lead::count()));
        $this->writeln(sprintf('  Deals:           %d', Deal::count()));
        $this->writeln(sprintf('  Deal products:   %d', DealProduct::count()));
        $this->writeln(sprintf('    Draft:         %d', Deal::where('pipeline_stage_id', 9)->count()));
        $this->writeln(sprintf('    Pending:       %d', Deal::where('pipeline_stage_id', 10)->count()));
        $this->writeln(sprintf('    Closed Won:    %d', Deal::where('pipeline_stage_id', 11)->count()));
        $this->writeln(sprintf('    Closed Lost:   %d', Deal::where('pipeline_stage_id', 12)->count()));
        $this->writeln(sprintf('  Quotes:          %d', Quote::count()));
        $this->writeln(sprintf('  Quote products:  %d', QuoteProduct::count()));
        $this->writeln(sprintf('    Draft:         %d', Quote::where('pipeline_stage_id', 13)->count()));
        $this->writeln(sprintf('    Sent:          %d', Quote::where('pipeline_stage_id', 14)->count()));
        $this->writeln(sprintf('    Accepted:      %d', Quote::where('pipeline_stage_id', 15)->count()));
        $this->writeln(sprintf('    Rejected:      %d', Quote::where('pipeline_stage_id', 16)->count()));
        $this->writeln(sprintf('    Ordered:       %d', Quote::where('pipeline_stage_id', 17)->count()));
        $this->writeln(sprintf('  Orders:          %d', Order::count()));
        $this->writeln(sprintf('  Order products:  %d', OrderProduct::count()));
        $this->writeln(sprintf('    From quotes:   %d', Order::whereNotNull('quote_id')->count()));
        $this->writeln(sprintf('    Standalone:    %d', Order::whereNull('quote_id')->count()));
        $this->writeln(sprintf('  Invoices:        %d', Invoice::count()));
        $this->writeln(sprintf('  Invoice lines:   %d', InvoiceLine::count()));
        $this->writeln(sprintf('    From orders:   %d', Invoice::whereNotNull('order_id')->count()));
        $this->writeln(sprintf('    Standalone:    %d', Invoice::whereNull('order_id')->count()));
        $this->writeln(sprintf('  Deliveries:      %d', Delivery::count()));
        $this->writeln(sprintf('  Delivery items:  %d', DeliveryProduct::count()));
        $this->writeln(sprintf('  Purchase orders: %d', PurchaseOrder::count()));
        $this->writeln(sprintf('  PO lines:        %d', PurchaseOrderLine::count()));
        $this->writeln(sprintf('    From orders:   %d', PurchaseOrder::whereNotNull('order_id')->count()));
        $this->writeln(sprintf('    Standalone:    %d', PurchaseOrder::whereNull('order_id')->count()));
        $this->writeln(sprintf('  Tasks:           %d', Task::count()));
        $this->writeln(sprintf('  Notes:           %d', Note::count()));
        $this->writeln(sprintf('  Calls:           %d', Call::count()));
        $this->writeln(sprintf('  Meetings:        %d', Meeting::count()));
        $this->writeln(sprintf('  Lunches:         %d', Lunch::count()));
        $this->writeln(sprintf('  Activities:      %d', Activity::count()));
        $this->writeln(sprintf('  Custom fields:   %d', Field::count()));
        $this->writeln(sprintf('  Field values:    %d', FieldValue::count()));
    }

    protected function step(string $label, callable $callback): void
    {
        $this->writeln('');
        $this->writeln(sprintf('▶ %s …', $label));
        $started = microtime(true);
        $callback();
        $this->writeln(sprintf('  ✓ Done (%.2fs)', microtime(true) - $started));
    }

    protected function writeln(string $message): void
    {
        if (isset($this->command)) {
            $this->command->getOutput()->writeln($message);
        }
    }

    protected function progressBar(int $max)
    {
        if (! isset($this->command)) {
            return null;
        }
        $bar = $this->command->getOutput()->createProgressBar($max);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s%');
        $bar->start();

        return $bar;
    }

    /**
     * Generate a plausible customer-supplied reference for a sales document
     * (quote / order / invoice / delivery / PO). Returns null some of the
     * time so the dataset has a realistic mix of "with" / "without" refs.
     *
     * @param  bool  $always  Force a non-null value (used for documents that
     *                        should always carry one, e.g. POs).
     */
    protected function randomReference(bool $always = false): ?string
    {
        if (! $always && ! $this->faker->boolean(70)) {
            return null;
        }

        $patterns = [
            fn () => 'PO-'.$this->faker->numerify('######'),
            fn () => 'PO#'.$this->faker->numerify('#####'),
            fn () => 'REF-'.strtoupper($this->faker->bothify('???-####')),
            fn () => 'Job '.$this->faker->numerify('#####'),
            fn () => 'JOB-'.$this->faker->numerify('####'),
            fn () => 'Tender #'.$this->faker->numerify('####'),
            fn () => 'Contract '.strtoupper($this->faker->bothify('??-####')),
            fn () => 'Q'.$this->faker->numerify('#####'),
            fn () => 'WO-'.$this->faker->numerify('######'),
            fn () => $this->faker->numerify('45#######'), // SAP-style 10-digit PO
            fn () => 'CR-'.$this->faker->numerify('#####'),
            fn () => 'Project '.strtoupper($this->faker->lexify('???')).'-'.$this->faker->numerify('###'),
        ];

        return $patterns[array_rand($patterns)]();
    }

    /* ----------------------------------------------------------------- */
    /* Users                                                             */
    /* ----------------------------------------------------------------- */

    protected function seedUsers(): void
    {
        $userClass = config('auth.providers.users.model', \App\User::class);

        // Sample users with the role each one should be assigned. Order
        // matters – the first user becomes the Owner.
        $sampleUsers = [
            ['name' => 'Sample Owner',   'email' => 'owner@sample.test',   'role' => 'Owner'],
            ['name' => 'Sample Admin',   'email' => 'admin@sample.test',   'role' => 'Admin'],
            ['name' => 'Sample Manager', 'email' => 'manager@sample.test', 'role' => 'Manager'],
            ['name' => 'Sample Sales',   'email' => 'sales@sample.test',   'role' => 'Employee'],
            ['name' => 'Sample Support', 'email' => 'support@sample.test', 'role' => 'Employee'],
        ];

        $hasCrmAccessColumn = Schema::hasColumn('users', 'crm_access');
        $rolesEnabled       = class_exists(\Spatie\Permission\Models\Role::class)
            && Schema::hasTable(config('permission.table_names.roles', 'roles'));

        // Ensure the four CRM roles exist so we can always assign them, even
        // if the package's table seeder hasn't been run yet.
        if ($rolesEnabled) {
            foreach (['Owner', 'Admin', 'Manager', 'Employee'] as $roleName) {
                $attrs = ['name' => $roleName, 'guard_name' => 'web'];
                if (config('permission.teams')) {
                    $attrs['team_id'] = null;
                }
                $values = Schema::hasColumn(config('permission.table_names.roles', 'roles'), 'crm_role')
                    ? ['crm_role' => 1]
                    : [];
                \Spatie\Permission\Models\Role::firstOrCreate($attrs, $values);
            }
        }

        $created = collect();

        foreach ($sampleUsers as $data) {
            $attributes = [
                'name'              => $data['name'],
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ];
            if ($hasCrmAccessColumn) {
                $attributes['crm_access'] = 1;
            }

            $user = $userClass::firstOrCreate(
                ['email' => $data['email']],
                $attributes
            );

            // Always (re)grant CRM access in case the user existed already.
            if ($hasCrmAccessColumn && (int) ($user->crm_access ?? 0) !== 1) {
                $user->forceFill(['crm_access' => 1])->save();
            }

            if ($rolesEnabled && method_exists($user, 'assignRole')) {
                if (! $user->hasRole($data['role'])) {
                    $user->assignRole($data['role']);
                }
            }

            $created->push($user->id);
        }

        // Bring the rest of the users (any pre-existing accounts) up to a
        // baseline: CRM access + at least one role.
        $existingUsers = $userClass::query()->get();
        foreach ($existingUsers as $user) {
            if ($hasCrmAccessColumn && (int) ($user->crm_access ?? 0) !== 1) {
                $user->forceFill(['crm_access' => 1])->save();
            }

            if ($rolesEnabled && method_exists($user, 'assignRole')) {
                if (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isEmpty()) {
                    $user->assignRole('Employee');
                }
            }
        }

        $this->userIds = $userClass::query()->pluck('id');
        if ($this->userIds->isEmpty()) {
            $this->userIds = $created;
        }
    }

    /* ----------------------------------------------------------------- */
    /* Teams                                                             */
    /* ----------------------------------------------------------------- */

    protected function seedTeams(): void
    {
        if (! Schema::hasTable('crm_teams')) {
            $this->teamIds = collect();

            return;
        }

        $ownerId = $this->userIds->first();

        foreach (['Sales Team', 'Account Management', 'Customer Success'] as $name) {
            $team = Team::firstOrCreate(['name' => $name], ['user_id' => $ownerId]);

            if (Schema::hasTable('crm_team_user')) {
                foreach ($this->userIds as $userId) {
                    DB::table('crm_team_user')->updateOrInsert(
                        ['crm_team_id' => $team->id, 'user_id' => $userId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        $this->teamIds = Team::pluck('id');
    }

    /* ----------------------------------------------------------------- */
    /* Tax Rates                                                         */
    /* ----------------------------------------------------------------- */

    protected function seedTaxRates(): void
    {
        // GST 10% – flagged as default so the UI picks it up automatically.
        $gst = TaxRate::firstOrCreate(
            ['name' => 'GST'],
            [
                'description' => 'Goods & Services Tax',
                'rate'        => 10,
                'default'     => Schema::hasColumn(config('laravel-crm.db_table_prefix').'tax_rates', 'default') ? 1 : null,
            ]
        );

        // Optional secondary rates so reports / drop-downs have variety.
        TaxRate::firstOrCreate(['name' => 'No Tax'], ['description' => 'Tax exempt',          'rate' => 0]);
        TaxRate::firstOrCreate(['name' => 'Reduced'], ['description' => 'Reduced rate (5%)',   'rate' => 5]);

        $this->defaultTaxRateId = $gst->id;
    }

    /* ----------------------------------------------------------------- */
    /* Lookups (lead sources, industries)                                */
    /* ----------------------------------------------------------------- */

    protected function seedLookups(): void
    {
        // Lead sources
        foreach (['Web', 'Referral', 'Email Campaign', 'Cold Call', 'Trade Show', 'Social Media', 'Partner'] as $name) {
            LeadSource::firstOrCreate(
                ['name' => $name],
                ['external_id' => Uuid::uuid4()->toString()]
            );
        }
        $this->leadSourceIds = LeadSource::pluck('id');

        // Industries
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing',
            'Education', 'Real Estate', 'Construction', 'Hospitality', 'Media',
            'Transport', 'Energy', 'Agriculture', 'Telecommunications',
        ];
        foreach ($industries as $name) {
            Industry::firstOrCreate(['name' => $name]);
        }
        $this->industryIds = Industry::pluck('id');

        // Cache OrganisationType / Label IDs already seeded by the tables seeder.
        $this->organisationTypeIds = OrganisationType::pluck('id');
        $this->labelIds            = Label::pluck('id');
    }

    /* ----------------------------------------------------------------- */
    /* Products & Categories                                             */
    /* ----------------------------------------------------------------- */

    protected function seedProductCatalogue(): void
    {
        foreach (['Software', 'Hardware', 'Services', 'Subscriptions', 'Training'] as $name) {
            ProductCategory::firstOrCreate(
                ['name' => $name],
                ['external_id' => Uuid::uuid4()->toString()]
            );
        }

        $categoryIds = ProductCategory::pluck('id');
        $taxRate     = $this->defaultTaxRateId
            ? TaxRate::find($this->defaultTaxRateId)
            : null;

        for ($i = 0; $i < 200; $i++) {
            $product = Product::create([
                'external_id'         => Uuid::uuid4()->toString(),
                'name'                => $this->faker->unique()->words(2, true),
                'description'         => $this->faker->sentence,
                'product_category_id' => $categoryIds->random(),
                'tax_rate_id'         => $this->defaultTaxRateId,
                'team_id'             => $this->randomTeamId(),
                'user_owner_id'       => $this->randomUserId(),
                'user_created_id'     => $this->randomUserId(),
            ]);

            // Pick a sensible unit price and ~50% cost price.
            $unitPrice = $this->faker->numberBetween(50, 5000);
            $costPrice = (int) round($unitPrice * $this->faker->randomFloat(2, 0.40, 0.65));

            // ProductPrice mutators ×100 → cents stored. Pass DOLLARS.
            ProductPrice::create([
                'external_id'   => Uuid::uuid4()->toString(),
                'product_id'    => $product->id,
                'team_id'       => $product->team_id,
                'unit_price'    => $unitPrice,
                'cost_per_unit' => $costPrice,
                'direct_cost'   => $costPrice,
                'currency'      => 'USD',
            ]);

            // Cache for downstream line-item generation (always in DOLLARS).
            $this->productPricing[$product->id] = [
                'unit_price' => $unitPrice,
                'cost'       => $costPrice,
                'tax_rate'   => $taxRate ? (float) $taxRate->rate : 10.0,
            ];
        }
    }

    /* ----------------------------------------------------------------- */
    /* Custom field groups & fields                                      */
    /* ----------------------------------------------------------------- */

    protected function seedCustomFields(): void
    {
        $blueprint = [
            [
                'group'  => 'Marketing Profile',
                'models' => [Lead::class, Deal::class],
                'fields' => [
                    ['name' => 'Source Channel',  'handle' => 'source_channel',  'type' => 'select',   'options' => ['Web', 'Referral', 'Event', 'Cold Outreach']],
                    ['name' => 'Campaign Code',   'handle' => 'campaign_code',   'type' => 'text'],
                    ['name' => 'Marketing Notes', 'handle' => 'marketing_notes', 'type' => 'textarea'],
                ],
            ],
            [
                'group'  => 'Sales Qualification',
                'models' => [Lead::class, Deal::class, Quote::class, Order::class],
                'fields' => [
                    ['name' => 'Budget Confirmed', 'handle' => 'budget_confirmed', 'type' => 'checkbox'],
                    ['name' => 'Decision Date',    'handle' => 'decision_date',    'type' => 'date'],
                    ['name' => 'Priority',         'handle' => 'priority',         'type' => 'radio', 'options' => ['Low', 'Medium', 'High']],
                ],
            ],
            [
                'group'  => 'Contact Profile',
                'models' => [Person::class, Organisation::class],
                'fields' => [
                    ['name' => 'LinkedIn URL',      'handle' => 'linkedin_url',      'type' => 'text'],
                    ['name' => 'Preferred Contact', 'handle' => 'preferred_contact', 'type' => 'select',          'options' => ['Email', 'Phone', 'SMS']],
                    ['name' => 'Languages',         'handle' => 'languages',         'type' => 'select_multiple', 'options' => ['English', 'Spanish', 'French', 'German']],
                ],
            ],
            [
                'group'  => 'Product Specification',
                'models' => [Product::class],
                'fields' => [
                    ['name' => 'SKU Reference',     'handle' => 'sku_reference',   'type' => 'text'],
                    ['name' => 'Warranty (months)', 'handle' => 'warranty_months', 'type' => 'text'],
                ],
            ],
        ];

        foreach ($blueprint as $groupSpec) {
            $group = FieldGroup::firstOrCreate(
                ['handle' => Str::slug($groupSpec['group'], '_')],
                [
                    'external_id' => Uuid::uuid4()->toString(),
                    'name'        => $groupSpec['group'],
                    'team_id'     => $this->randomTeamId(),
                ]
            );

            foreach ($groupSpec['fields'] as $fieldSpec) {
                $field = Field::firstOrCreate(
                    ['field_group_id' => $group->id, 'handle' => $fieldSpec['handle']],
                    [
                        'external_id' => Uuid::uuid4()->toString(),
                        'name'        => $fieldSpec['name'],
                        'type'        => $fieldSpec['type'],
                        'team_id'     => $group->team_id,
                        'required'    => false,
                    ]
                );

                foreach ($groupSpec['models'] as $modelClass) {
                    FieldModel::firstOrCreate(
                        ['field_id' => $field->id, 'model' => $modelClass],
                        ['external_id' => Uuid::uuid4()->toString(), 'team_id' => $group->team_id]
                    );
                }

                if (! empty($fieldSpec['options'])) {
                    foreach ($fieldSpec['options'] as $order => $option) {
                        FieldOption::firstOrCreate(
                            ['field_id' => $field->id, 'value' => Str::slug($option, '_')],
                            [
                                'external_id' => Uuid::uuid4()->toString(),
                                'label'       => $option,
                                'order'       => $order,
                                'team_id'     => $group->team_id,
                            ]
                        );
                    }
                }
            }
        }
    }

    /* ----------------------------------------------------------------- */
    /* Organisations & People                                            */
    /* ----------------------------------------------------------------- */

    protected function seedOrganisationsAndPeople(): void
    {
        $bar = $this->progressBar(500 + 1200);

        for ($i = 0; $i < 500; $i++) {
            $companyName = $this->faker->unique()->company;
            $domain      = strtolower(preg_replace('/[^a-z0-9]/i', '', $companyName)).'.example.com';

            $org = Organisation::create([
                'external_id'         => Uuid::uuid4()->toString(),
                'name'                => $companyName,
                'description'         => $this->faker->catchPhrase,
                'industry_id'         => $this->industryIds && $this->industryIds->isNotEmpty()
                                             ? $this->industryIds->random()
                                             : null,
                'organisation_type_id' => $this->organisationTypeIds && $this->organisationTypeIds->isNotEmpty()
                                             ? $this->organisationTypeIds->random()
                                             : null,
                'vat_number'          => strtoupper($this->faker->bothify('??########')),
                'domain'              => $domain,
                'website_url'         => 'https://'.$domain,
                'year_founded'        => $this->faker->numberBetween(1950, 2024),
                // setAnnualRevenueAttribute / setTotalMoneyRaisedAttribute ×100; pass dollars.
                // annual_revenue is INT(32-bit) – max ~$21M after ×100 to fit cents.
                'annual_revenue'      => $this->faker->numberBetween(50_000, 20_000_000),
                // total_money_raised is BIGINT so a wider range is fine.
                'total_money_raised'  => $this->faker->numberBetween(0, 50_000_000),
                'number_of_employees' => $this->faker->numberBetween(1, 5000),
                'linkedin'            => 'https://linkedin.com/company/'.$this->faker->slug(2),
                'facebook'            => 'https://facebook.com/'.$this->faker->slug(2),
                'twitter'             => 'https://twitter.com/'.$this->faker->userName,
                'instagram'           => 'https://instagram.com/'.$this->faker->userName,
                'team_id'             => $this->randomTeamId(),
                'user_owner_id'       => $this->randomUserId(),
                'user_created_id'     => $this->randomUserId(),
            ]);

            $this->addContactDetails($org, 'organisation');
            $this->attachRandomLabels($org);
            $bar && $bar->advance();
        }

        $organisationIds = Organisation::pluck('id');

        for ($i = 0; $i < 1200; $i++) {
            $person = Person::create([
                'external_id'     => Uuid::uuid4()->toString(),
                'first_name'      => $this->faker->firstName,
                'middle_name'     => $this->faker->boolean(30) ? $this->faker->firstName : null,
                'last_name'       => $this->faker->lastName,
                'title'           => $this->faker->randomElement(['Mr', 'Ms', 'Mrs', 'Dr', null]),
                'gender'          => $this->faker->randomElement(['male', 'female', null]),
                'birthday'        => $this->faker->boolean(60)
                                         ? Carbon::now()
                                             ->subYears($this->faker->numberBetween(20, 70))
                                             ->subDays($this->faker->numberBetween(0, 365))
                                             ->format($this->dateFormat)
                                         : null,
                'description'     => $this->faker->sentence,
                'organisation_id' => $organisationIds->isNotEmpty() && $this->faker->boolean(80)
                                         ? $organisationIds->random()
                                         : null,
                'team_id'         => $this->randomTeamId(),
                'user_owner_id'   => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
            ]);

            $this->addContactDetails($person, 'person');
            $this->attachRandomLabels($person);
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Attach 0–2 random labels (Hot/Cold/Warm) to a model.
     */
    protected function attachRandomLabels($model): void
    {
        if (! $this->labelIds || $this->labelIds->isEmpty()) {
            return;
        }

        // ~60% of records get at least one label.
        if (! $this->faker->boolean(60)) {
            return;
        }

        $count = $this->faker->numberBetween(1, min(2, $this->labelIds->count()));
        $ids   = $this->labelIds->shuffle()->take($count)->all();

        try {
            $model->labels()->syncWithoutDetaching($ids);
        } catch (\Throwable $e) {
            // Swallow – labels relation might not exist on every model.
        }
    }

    /**
     * Add a primary email, a primary phone, and 1–2 addresses to a Person
     * or Organisation model.
     *
     * Address types used:
     *   Organisations: 5 Billing (always) + 50% chance of 6 Shipping
     *   People:        1 Current (always) + 35% chance of 3 Postal
     */
    protected function addContactDetails($model, string $type): void
    {
        $teamId = $model->team_id;

        // Email
        Email::create([
            'external_id'    => Uuid::uuid4()->toString(),
            'emailable_type' => get_class($model),
            'emailable_id'   => $model->id,
            'address'        => $this->faker->unique()->safeEmail,
            'primary'        => 1,
            'team_id'        => $teamId,
        ]);

        // Phone
        Phone::create([
            'external_id'    => Uuid::uuid4()->toString(),
            'phoneable_type' => get_class($model),
            'phoneable_id'   => $model->id,
            'number'         => $this->faker->phoneNumber,
            'primary'        => 1,
            'team_id'        => $teamId,
        ]);

        // Addresses
        if ($type === 'organisation') {
            // Billing address (always)
            Address::create([
                'external_id'      => Uuid::uuid4()->toString(),
                'addressable_type' => get_class($model),
                'addressable_id'   => $model->id,
                'address_type_id'  => 5, // Billing
                'line1'            => $this->faker->streetAddress,
                'city'             => $this->faker->city,
                'state'            => $this->faker->stateAbbr,
                'code'             => $this->faker->postcode,
                'country'          => $this->faker->country,
                'primary'          => 1,
                'team_id'          => $teamId,
            ]);

            // Shipping address (50%)
            if ($this->faker->boolean(50)) {
                Address::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'addressable_type' => get_class($model),
                    'addressable_id'   => $model->id,
                    'address_type_id'  => 6, // Shipping
                    'line1'            => $this->faker->streetAddress,
                    'city'             => $this->faker->city,
                    'state'            => $this->faker->stateAbbr,
                    'code'             => $this->faker->postcode,
                    'country'          => $this->faker->country,
                    'primary'          => 0,
                    'team_id'          => $teamId,
                ]);
            }
        } else {
            // Current address (always)
            Address::create([
                'external_id'      => Uuid::uuid4()->toString(),
                'addressable_type' => get_class($model),
                'addressable_id'   => $model->id,
                'address_type_id'  => 1, // Current
                'line1'            => $this->faker->streetAddress,
                'city'             => $this->faker->city,
                'state'            => $this->faker->stateAbbr,
                'code'             => $this->faker->postcode,
                'country'          => $this->faker->country,
                'primary'          => 1,
                'team_id'          => $teamId,
            ]);

            // Postal address (35%)
            if ($this->faker->boolean(35)) {
                Address::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'addressable_type' => get_class($model),
                    'addressable_id'   => $model->id,
                    'address_type_id'  => 3, // Postal
                    'line1'            => $this->faker->streetAddress,
                    'city'             => $this->faker->city,
                    'state'            => $this->faker->stateAbbr,
                    'code'             => $this->faker->postcode,
                    'country'          => $this->faker->country,
                    'primary'          => 0,
                    'team_id'          => $teamId,
                ]);
            }
        }
    }

    /* ----------------------------------------------------------------- */
    /* Clients                                                           */
    /* ----------------------------------------------------------------- */

    protected function seedClients(): void
    {
        $organisations = Organisation::inRandomOrder()->limit(200)->get();
        $people        = Person::inRandomOrder()->limit(150)->get();

        $bar = $this->progressBar($organisations->count() + $people->count());

        foreach ($organisations as $organisation) {
            Client::create([
                'external_id'     => Uuid::uuid4()->toString(),
                'name'            => $organisation->name,
                'clientable_type' => Organisation::class,
                'clientable_id'   => $organisation->id,
                'team_id'         => $organisation->team_id,
                'user_owner_id'   => $organisation->user_owner_id,
                'user_created_id' => $this->randomUserId(),
            ]);
            $bar && $bar->advance();
        }

        foreach ($people as $person) {
            Client::create([
                'external_id'     => Uuid::uuid4()->toString(),
                'name'            => trim($person->first_name.' '.$person->last_name),
                'clientable_type' => Person::class,
                'clientable_id'   => $person->id,
                'team_id'         => $person->team_id,
                'user_owner_id'   => $person->user_owner_id,
                'user_created_id' => $this->randomUserId(),
            ]);
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Contacts (link people to organisations as Primary/Secondary)      */
    /* ----------------------------------------------------------------- */

    protected function seedContacts(): void
    {
        $primaryTypeId   = ContactType::where('name', 'Primary')->value('id');
        $secondaryTypeId = ContactType::where('name', 'Secondary')->value('id');

        if (! $primaryTypeId) {
            return;
        }

        // Group people by organisation – the first becomes Primary, the rest Secondary.
        $people = Person::whereNotNull('organisation_id')->get()->groupBy('organisation_id');

        $bar = $this->progressBar($people->count());

        foreach ($people as $organisationId => $orgPeople) {
            $organisation = Organisation::find($organisationId);
            if (! $organisation) {
                $bar && $bar->advance();

                continue;
            }

            foreach ($orgPeople as $index => $person) {
                $contact = Contact::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'team_id'          => $organisation->team_id,
                    'contactable_type' => Person::class,
                    'contactable_id'   => $person->id,
                    'entityable_type'  => Organisation::class,
                    'entityable_id'    => $organisation->id,
                    'user_created_id'  => $this->randomUserId(),
                ]);

                $typeId = ($index === 0) ? $primaryTypeId : ($secondaryTypeId ?? $primaryTypeId);

                DB::table(config('laravel-crm.db_table_prefix').'contact_contact_type')->insert([
                    'contact_id'      => $contact->id,
                    'contact_type_id' => $typeId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Leads & Deals                                                     */
    /* ----------------------------------------------------------------- */

    protected function seedLeadsAndDeals(): void
    {
        $organisationIds = Organisation::pluck('id');
        $peopleIds       = Person::pluck('id');

        // Lead pipeline stages 1–8 with weighted distribution.
        $leadStageDist = [
            20  => 1,  // New
            35  => 2,  // Appointment Scheduled
            50  => 3,  // Qualified To Buy
            60  => 4,  // Presentation Scheduled
            70  => 5,  // Decision Maker Bought-In
            85  => 6,  // Contract Sent
            93  => 7,  // Closed Won
            100 => 8,  // Closed Lost
        ];

        $bar = $this->progressBar(400);

        for ($i = 0; $i < 400; $i++) {
            $leadStageId = $this->weightedRandom($leadStageDist);

            $lead = Lead::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'pipeline_id'       => 1,
                'pipeline_stage_id' => $leadStageId,
                'lead_source_id'    => $this->leadSourceIds && $this->leadSourceIds->isNotEmpty()
                                           ? $this->leadSourceIds->random()
                                           : null,
                'qualified'         => $this->faker->boolean(40),
                'title'             => $this->faker->catchPhrase,
                'description'       => $this->faker->paragraph,
                // setAmountAttribute ×100; pass dollars.
                'amount'            => $this->faker->numberBetween(100, 100000),
                'currency'          => 'USD',
                'person_id'         => $peopleIds->isNotEmpty() ? $peopleIds->random() : null,
                'organisation_id'   => $organisationIds->isNotEmpty() ? $organisationIds->random() : null,
                'expected_close'    => Carbon::now()->addDays($this->faker->numberBetween(7, 90))->format($this->dateFormat),
                'team_id'           => $this->randomTeamId(),
                'user_owner_id'     => $this->randomUserId(),
                'user_assigned_id'  => $this->randomUserId(),
                'user_created_id'   => $this->randomUserId(),
                'created_at'        => Carbon::now()->subDays($this->faker->numberBetween(0, 120)),
            ]);

            $this->attachRandomLabels($lead);

            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }

        // Deal pipeline: 9 Draft 20%, 10 Pending 30%
        //                11 Closed Won 30%, 12 Closed Lost 20%
        $dealStageDist = [
            20  => 9,   // Draft
            50  => 10,  // Pending
            80  => 11,  // Closed Won
            100 => 12,  // Closed Lost
        ];

        $leads = Lead::inRandomOrder()->limit(250)->get();
        $bar2  = $this->progressBar($leads->count());

        foreach ($leads as $lead) {
            $expectedClose = $lead->expected_close
                ? Carbon::parse($lead->expected_close)->format($this->dateFormat)
                : null;

            // Lead amount is stored in cents; divide back to dollars for Deal mutator.
            $dealAmount = $lead->amount !== null ? (int) ($lead->amount / 100) : null;

            $dealStageId = $this->weightedRandom($dealStageDist);

            $closedAt     = null;
            $closedStatus = null;
            if ($dealStageId === 11) {
                $closedAt     = Carbon::now()->subDays($this->faker->numberBetween(1, 60));
                $closedStatus = 'won';
            } elseif ($dealStageId === 12) {
                $closedAt     = Carbon::now()->subDays($this->faker->numberBetween(1, 60));
                $closedStatus = 'lost';
            }

            $deal = Deal::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'lead_id'           => $lead->id,
                'pipeline_id'       => 2,
                'pipeline_stage_id' => $dealStageId,
                'person_id'         => $lead->person_id,
                'organisation_id'   => $lead->organisation_id,
                'title'             => $lead->title.' (Deal)',
                'description'       => $this->faker->paragraph,
                // setAmountAttribute ×100; pass dollars.
                'amount'   => $dealAmount,
                'currency' => 'USD',
                'qualified'         => $this->faker->boolean(70),
                'expected_close'    => $expectedClose,
                'closed_at'         => $closedAt,
                'closed_status'     => $closedStatus,
                'team_id'           => $lead->team_id,
                'user_owner_id'     => $lead->user_owner_id,
                'user_assigned_id'  => $lead->user_assigned_id,
                'user_created_id'   => $this->randomUserId(),
                'created_at'        => Carbon::now()->subDays($this->faker->numberBetween(0, 30)),
            ]);

            $this->attachRandomLabels($deal);

            $bar2 && $bar2->advance();
        }

        if ($bar2) {
            $bar2->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Line item helpers                                                 */
    /* ----------------------------------------------------------------- */

    /**
     * Lazily ensure the productPricing cache is populated. When the seeder
     * is run repeatedly (or only individual steps are run), the cache may
     * be empty even though products already exist – rebuild from the DB.
     */
    protected function ensureProductPricing(): void
    {
        if (! empty($this->productPricing)) {
            return;
        }

        $taxRateById = TaxRate::pluck('rate', 'id')->all();
        $defaultRate = $this->defaultTaxRateId
            ? ($taxRateById[$this->defaultTaxRateId] ?? 10)
            : 10;

        Product::with('productPrices')->chunk(200, function ($products) use ($taxRateById, $defaultRate) {
            foreach ($products as $product) {
                $price = $product->productPrices->first();

                // ProductPrice columns store cents – divide back to dollars.
                $unitPrice = $price && $price->unit_price
                    ? (int) ($price->unit_price / 100)
                    : $this->faker->numberBetween(50, 5000);
                $costPrice = $price && $price->cost_per_unit
                    ? (int) ($price->cost_per_unit / 100)
                    : (int) round($unitPrice * 0.5);
                $rate = isset($taxRateById[$product->tax_rate_id])
                    ? (float) $taxRateById[$product->tax_rate_id]
                    : (float) $defaultRate;

                $this->productPricing[$product->id] = [
                    'unit_price' => $unitPrice,
                    'cost'       => $costPrice,
                    'tax_rate'   => $rate,
                ];
            }
        });
    }

    /**
     * Generate $count line items in DOLLAR units.
     *
     * @param  int   $count       Number of lines to generate (default 1–4)
     * @param  bool  $useCost     Use cost price instead of unit price (for POs)
     * @return array<int, array{product_id:int, quantity:int, price:int|float, amount:int|float, tax_rate:float, tax_dollars:float, tax_cents:int, price_cents:int, amount_cents:int}>
     */
    protected function generateLineItems(int $count = null, bool $useCost = false): array
    {
        $this->ensureProductPricing();

        if (empty($this->productPricing)) {
            return [];
        }

        $count       = $count ?? $this->faker->numberBetween(1, 4);
        $productIds  = array_keys($this->productPricing);
        $picked      = (array) array_rand(array_flip($productIds), min($count, count($productIds)));
        $items       = [];

        foreach ((array) $picked as $productId) {
            $info     = $this->productPricing[$productId];
            $quantity = $this->faker->numberBetween(1, 10);
            $price    = $useCost ? $info['cost'] : $info['unit_price'];
            $amount   = $quantity * $price;
            $rate     = $info['tax_rate'];

            // Integer cents (the source of truth for parent doc totals).
            $priceCents  = (int) ($price * 100);
            $amountCents = $quantity * $priceCents;
            $taxCents    = (int) round($amountCents * ($rate / 100));

            // Dollar equivalents for any path that goes through model mutators.
            $taxDollars  = round($taxCents / 100, 2);

            $items[] = [
                'product_id'   => (int) $productId,
                'quantity'     => $quantity,
                'price'        => $price,        // dollars
                'amount'       => $amount,       // dollars
                'tax_rate'     => $rate,         // e.g. 10.0
                'tax_dollars'  => $taxDollars,   // for InvoiceLine / PurchaseOrderLine (mutator ×100)
                'tax_cents'    => $taxCents,     // for QuoteProduct / OrderProduct (raw cents column)
                'price_cents'  => $priceCents,   // exact cents (for parent doc totals)
                'amount_cents' => $amountCents,  // exact cents (for parent doc totals)
            ];
        }

        return $items;
    }

    /**
     * Sum line items into a parent doc's subtotal/tax/total in DOLLARS *and*
     * exact integer CENTS (the latter is the source of truth and should be
     * written directly to the parent via setMoneyCents() to avoid float drift).
     *
     * @param  array  $lines
     * @return array{subtotal:float|int, tax:float, total:float, subtotal_cents:int, tax_cents:int, total_cents:int}
     */
    protected function summariseLines(array $lines): array
    {
        $subtotalCents = 0;
        $taxCents      = 0;
        foreach ($lines as $line) {
            $subtotalCents += (int) $line['amount_cents'];
            $taxCents      += (int) $line['tax_cents'];
        }
        $totalCents = $subtotalCents + $taxCents;

        return [
            'subtotal'       => $subtotalCents / 100,
            'tax'            => round($taxCents / 100, 2),
            'total'          => round($totalCents / 100, 2),
            'subtotal_cents' => $subtotalCents,
            'tax_cents'      => $taxCents,
            'total_cents'    => $totalCents,
        ];
    }

    /**
     * Write exact integer cent values directly to a parent doc's money
     * columns, bypassing the model's ×100 mutators. This is the *only*
     * reliable way to keep parent totals byte-exact with the line items
     * because float math through the mutators can drift by a cent.
     *
     * Always defaults discount and adjustments to 0 so that
     * CheckAmount\total() doesn't perform NULL arithmetic.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Model  $model
     * @param  array<string, int>  $cents  e.g. ['subtotal'=>$c, 'tax'=>$c, 'total'=>$c]
     */
    protected function setMoneyCents($model, array $cents): void
    {
        $payload = $cents + [
            'discount'    => 0,
            'adjustments' => 0,
        ];

        DB::table($model->getTable())
            ->where('id', $model->id)
            ->update($payload);
    }

    /* ----------------------------------------------------------------- */
    /* Deal Products                                                     */
    /* ----------------------------------------------------------------- */

    protected function seedDealProducts(): void
    {
        $deals = Deal::all();

        if ($deals->isEmpty() || empty($this->productPricing) && Product::count() === 0) {
            return;
        }

        $bar = $this->progressBar($deals->count());

        foreach ($deals as $deal) {
            $lines = $this->generateLineItems();

            if (empty($lines)) {
                $bar && $bar->advance();

                continue;
            }

            $dealSubtotal = 0;

            foreach ($lines as $line) {
                DealProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'deal_id'     => $deal->id,
                    'product_id'  => $line['product_id'],
                    'quantity'    => $line['quantity'],
                    // DealProduct mutators ×100 → cents stored. Pass DOLLARS.
                    'price'       => $line['price'],
                    'amount'      => $line['amount'],
                    // DealProduct has NO tax_amount mutator – column stores raw cents.
                    'tax_rate'    => $line['tax_rate'],
                    'tax_amount'  => $line['tax_cents'],
                    'currency'    => 'USD',
                    'team_id'     => $deal->team_id,
                ]);

                $dealSubtotal += $line['amount'];
            }

            // Update the deal's amount to reflect the real product total.
            // setAmountAttribute ×100; pass dollars.
            $deal->amount = $dealSubtotal;
            $deal->save();

            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Quotes                                                            */
    /* ----------------------------------------------------------------- */

    protected function seedQuotes(): void
    {
        $deals = Deal::all();

        // Quote pipeline: 13 Draft 10%, 14 Sent 20%, 15 Accepted 50%, 16 Rejected 20%
        $quoteStageDist = [
            10  => 13,  // Draft
            30  => 14,  // Sent
            80  => 15,  // Accepted
            100 => 16,  // Rejected
        ];

        $docNumber = 1000;
        $bar       = $this->progressBar($deals->count());

        foreach ($deals as $deal) {
            $stageId = $this->weightedRandom($quoteStageDist);

            $daysAgo  = $this->faker->numberBetween(5, 60);
            $issuedAt = Carbon::now()->subDays($daysAgo);

            $acceptedAt = null;
            $rejectedAt = null;

            if ($stageId === 15) {
                $acceptedAt = (clone $issuedAt)->addDays($this->faker->numberBetween(1, 14));
            } elseif ($stageId === 16) {
                $rejectedAt = (clone $issuedAt)->addDays($this->faker->numberBetween(1, 14));
            }

            // Generate real line items so subtotal / tax / total all add up.
            $lines   = $this->generateLineItems();
            $totals  = $this->summariseLines($lines);

            $quote = Quote::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'lead_id'           => $deal->lead_id,
                'deal_id'           => $deal->id,
                'person_id'         => $deal->person_id,
                'organisation_id'   => $deal->organisation_id,
                'pipeline_id'       => 3,
                'pipeline_stage_id' => $stageId,
                'quote_id'          => 'Q-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'number'            => $docNumber,
                'title'             => $deal->title,
                'description'       => $this->faker->sentence,
                'reference'         => $this->randomReference(),
                'issue_at'          => $issuedAt->format($this->dateFormat),
                'expire_at'         => (clone $issuedAt)->addDays($this->faker->numberBetween(15, 60))->format($this->dateFormat),
                'accepted_at'       => $acceptedAt,
                'rejected_at'       => $rejectedAt,
                'currency'          => 'USD',
                // DOLLARS – mutators ×100 → cents stored.
                'subtotal' => $totals['subtotal'],
                'tax'      => $totals['tax'],
                'total'    => $totals['total'],
                'team_id'           => $deal->team_id,
                'user_owner_id'     => $deal->user_owner_id,
                'user_assigned_id'  => $deal->user_assigned_id,
                'user_created_id'   => $this->randomUserId(),
            ]);

            foreach ($lines as $line) {
                QuoteProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'quote_id'    => $quote->id,
                    'product_id'  => $line['product_id'],
                    'quantity'    => $line['quantity'],
                    // QuoteProduct mutators ×100 → cents stored. Pass DOLLARS.
                    'price'       => $line['price'],
                    'amount'      => $line['amount'],
                    // QuoteProduct has NO tax_amount mutator – column stores raw cents.
                    'tax_rate'    => $line['tax_rate'],
                    'tax_amount'  => $line['tax_cents'],
                    'currency'    => 'USD',
                    'team_id'     => $deal->team_id,
                ]);
            }

            // Overwrite parent money columns with EXACT integer cents so
            // subtotal / tax / total reconcile byte-for-byte with the lines.
            $this->setMoneyCents($quote, [
                'subtotal' => $totals['subtotal_cents'],
                'tax'      => $totals['tax_cents'],
                'total'    => $totals['total_cents'],
            ]);

            $this->attachRandomLabels($quote);

            $docNumber++;
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Orders                                                            */
    /* ----------------------------------------------------------------- */

    protected function seedOrders(): void
    {
        // Order pipeline: 18 Draft, 19 Open, 20 Invoiced, 21 Delivered, 22 Completed
        $orderStageDist = [
            10  => 18,  // Draft
            30  => 19,  // Open
            50  => 20,  // Invoiced
            75  => 21,  // Delivered
            100 => 22,  // Completed
        ];

        $docNumber = 5000;

        // ── Build a combined, interleaved task list ──────────────────────
        // Convert ~80% of Accepted quotes into orders, and mix in a batch of
        // standalone (non-converted) orders so the resulting numbering /
        // created_at ordering is not "all converted, then all standalone".
        $acceptedQuotes = Quote::with('quoteProducts')->where('pipeline_stage_id', 15)->get();

        $tasks = [];
        foreach ($acceptedQuotes as $quote) {
            if ($this->faker->boolean(80)) {
                $tasks[] = ['type' => 'quote', 'quote' => $quote];
            }
        }

        $standaloneCount = max(80, (int) ((count($tasks) + 1) * 0.30));
        for ($i = 0; $i < $standaloneCount; $i++) {
            $tasks[] = ['type' => 'standalone'];
        }

        shuffle($tasks);

        $organisationIds = Organisation::pluck('id');
        $peopleIds       = Person::pluck('id');

        $bar = $this->progressBar(count($tasks));

        foreach ($tasks as $task) {
            if ($task['type'] !== 'quote') {
                // Standalone order – handled in Part 2 block below.
                $this->createStandaloneOrder($docNumber, $orderStageDist, $organisationIds, $peopleIds);
                $docNumber++;
                $bar && $bar->advance();

                continue;
            }

            $quote   = $task['quote'];
            $stageId = $this->weightedRandom($orderStageDist);

            // Mirror the quote's line items into OrderProducts so totals match
            // the quote and Quote::orderComplete() returns true.
            $orderSubtotal = 0;
            $orderTaxCents = 0;
            $linesToInsert = [];

            foreach ($quote->quoteProducts as $qp) {
                // Quote product columns store cents directly – convert back.
                $priceDollars  = $qp->price !== null ? $qp->price / 100 : 0;
                $amountDollars = $qp->amount !== null ? $qp->amount / 100 : 0;
                $taxCents      = (int) ($qp->tax_amount ?? 0);

                $linesToInsert[] = [
                    'quote_product_id' => $qp->id,
                    'product_id'       => $qp->product_id,
                    'quantity'         => $qp->quantity,
                    'price'            => $priceDollars,
                    'amount'           => $amountDollars,
                    'tax_rate'         => $qp->tax_rate,
                    'tax_cents'        => $taxCents,
                ];

                $orderSubtotal += $amountDollars;
                $orderTaxCents += $taxCents;
            }

            // Fallback if the quote had no lines (shouldn't happen now, but be safe).
            if (empty($linesToInsert)) {
                $generated = $this->generateLineItems();
                foreach ($generated as $g) {
                    $linesToInsert[] = [
                        'quote_product_id' => null,
                        'product_id'       => $g['product_id'],
                        'quantity'         => $g['quantity'],
                        'price'            => $g['price'],
                        'amount'           => $g['amount'],
                        'tax_rate'         => $g['tax_rate'],
                        'tax_cents'        => $g['tax_cents'],
                    ];
                    $orderSubtotal += $g['amount'];
                    $orderTaxCents += $g['tax_cents'];
                }
            }

            $taxDollars = round($orderTaxCents / 100, 2);
            $total      = round($orderSubtotal + $taxDollars, 2);

            $order = Order::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'lead_id'           => $quote->lead_id,
                'deal_id'           => $quote->deal_id,
                'quote_id'          => $quote->id,
                'person_id'         => $quote->person_id,
                'organisation_id'   => $quote->organisation_id,
                'pipeline_id'       => 4,
                'pipeline_stage_id' => $stageId,
                'order_id'          => 'O-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'number'            => $docNumber,
                'description'       => $this->faker->sentence,
                'reference'         => $this->randomReference(),
                'currency'          => 'USD',
                // DOLLARS – mutators ×100 → cents stored.
                'subtotal' => $orderSubtotal,
                'tax'      => $taxDollars,
                'total'    => $total,
                'team_id'           => $quote->team_id,
                'user_owner_id'     => $quote->user_owner_id,
                'user_assigned_id'  => $quote->user_assigned_id,
                'user_created_id'   => $this->randomUserId(),
            ]);

            foreach ($linesToInsert as $line) {
                OrderProduct::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'order_id'         => $order->id,
                    'quote_product_id' => $line['quote_product_id'],
                    'product_id'       => $line['product_id'],
                    'quantity'         => $line['quantity'],
                    // OrderProduct mutators ×100 → cents stored. Pass DOLLARS.
                    'price'            => $line['price'],
                    'amount'            => $line['amount'],
                    // OrderProduct has NO tax_amount mutator – store raw cents.
                    'tax_rate'         => $line['tax_rate'],
                    'tax_amount'       => $line['tax_cents'],
                    'currency'         => 'USD',
                    'team_id'          => $order->team_id,
                ]);
            }

            // Overwrite money columns with EXACT integer cents derived from
            // the underlying quote line items.
            $orderSubtotalCents = (int) round($orderSubtotal * 100);
            $orderTotalCents    = $orderSubtotalCents + $orderTaxCents;
            $this->setMoneyCents($order, [
                'subtotal' => $orderSubtotalCents,
                'tax'      => $orderTaxCents,
                'total'    => $orderTotalCents,
            ]);

            // Mark the quote as "Ordered" (stage 17).
            $quote->pipeline_stage_id = 17;
            $quote->save();

            // Every order gets a billing + shipping address.
            $this->attachOrderAddresses($order);

            $this->attachRandomLabels($order);

            $docNumber++;
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Create a single standalone (not-from-quote) order with random line
     * items. Extracted so it can be invoked from the interleaved task loop.
     */
    protected function createStandaloneOrder(int $docNumber, array $orderStageDist, $organisationIds, $peopleIds): void
    {
        $stageId = $this->weightedRandom($orderStageDist);
        $lines   = $this->generateLineItems();
        $totals  = $this->summariseLines($lines);

        $order = Order::create([
            'external_id'       => Uuid::uuid4()->toString(),
            'person_id'         => $peopleIds->isNotEmpty() ? $peopleIds->random() : null,
            'organisation_id'   => $organisationIds->isNotEmpty() ? $organisationIds->random() : null,
            'pipeline_id'       => 4,
            'pipeline_stage_id' => $stageId,
            'order_id'          => 'O-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
            'number'            => $docNumber,
            'description'       => $this->faker->sentence,
            'reference'         => $this->randomReference(),
            'currency'          => 'USD',
            // DOLLARS – mutators ×100 → cents stored.
            'subtotal' => $totals['subtotal'],
            'tax'      => $totals['tax'],
            'total'    => $totals['total'],
            'team_id'           => $this->randomTeamId(),
            'user_owner_id'     => $this->randomUserId(),
            'user_assigned_id'  => $this->randomUserId(),
            'user_created_id'   => $this->randomUserId(),
        ]);

        foreach ($lines as $line) {
            OrderProduct::create([
                'external_id' => Uuid::uuid4()->toString(),
                'order_id'    => $order->id,
                'product_id'  => $line['product_id'],
                'quantity'    => $line['quantity'],
                'price'       => $line['price'],
                'amount'      => $line['amount'],
                'tax_rate'    => $line['tax_rate'],
                'tax_amount'  => $line['tax_cents'],
                'currency'    => 'USD',
                'team_id'     => $order->team_id,
            ]);
        }

        $this->setMoneyCents($order, [
            'subtotal' => $totals['subtotal_cents'],
            'tax'      => $totals['tax_cents'],
            'total'    => $totals['total_cents'],
        ]);

        // Every order gets a billing + shipping address.
        $this->attachOrderAddresses($order);

        $this->attachRandomLabels($order);
    }

    /* ----------------------------------------------------------------- */
    /* Invoices                                                          */
    /* ----------------------------------------------------------------- */

    protected function seedInvoices(): void
    {
        // Invoice pipeline: 23 Draft 10%, 24 Awaiting Approval 15%,
        //                   25 Awaiting Payment 30%, 26 Paid 45%
        $invStageDist = [
            10  => 23,  // Draft
            25  => 24,  // Awaiting Approval
            55  => 25,  // Awaiting Payment
            100 => 26,  // Paid
        ];

        $docNumber = 8000;

        // ── Build a combined, interleaved task list ──────────────────────
        // ~75% of orders get an invoice, mixed in with standalone invoices
        // so numbering / created_at order is interleaved rather than
        // "all converted, then all standalone".
        $orders = Order::with('orderProducts')->get();

        $tasks = [];
        foreach ($orders as $order) {
            if ($this->faker->boolean(75)) {
                $tasks[] = ['type' => 'order', 'order' => $order];
            }
        }

        $standaloneCount = max(60, (int) ((count($tasks) + 1) * 0.20));
        for ($i = 0; $i < $standaloneCount; $i++) {
            $tasks[] = ['type' => 'standalone'];
        }

        shuffle($tasks);

        $organisationIds = Organisation::pluck('id');
        $peopleIds       = Person::pluck('id');

        $bar = $this->progressBar(count($tasks));

        foreach ($tasks as $task) {
            if ($task['type'] !== 'order') {
                $this->createStandaloneInvoice($docNumber, $invStageDist, $organisationIds, $peopleIds);
                $docNumber++;
                $bar && $bar->advance();

                continue;
            }

            $order   = $task['order'];
            $stageId = $this->weightedRandom($invStageDist);

            // Mirror the order's lines into invoice lines so totals match.
            $invSubtotalCents = 0;
            $invTaxCents      = 0;
            $linesToInsert    = [];

            foreach ($order->orderProducts as $op) {
                $priceDollars  = $op->price !== null ? $op->price / 100 : 0;
                $amountDollars = $op->amount !== null ? $op->amount / 100 : 0;
                // Order product tax_amount is RAW cents.
                $taxCents      = (int) ($op->tax_amount ?? 0);
                $taxDollars    = round($taxCents / 100, 2);
                $amountCents   = (int) ($op->amount ?? 0);

                $linesToInsert[] = [
                    'order_product_id' => $op->id,
                    'product_id'       => $op->product_id,
                    'quantity'         => $op->quantity,
                    'price'            => $priceDollars,
                    'amount'           => $amountDollars,
                    'tax_rate'         => $op->tax_rate,
                    'tax_dollars'      => $taxDollars,
                ];

                $invSubtotalCents += $amountCents;
                $invTaxCents      += $taxCents;
            }

            if (empty($linesToInsert)) {
                $generated = $this->generateLineItems();
                foreach ($generated as $g) {
                    $linesToInsert[] = [
                        'order_product_id' => null,
                        'product_id'       => $g['product_id'],
                        'quantity'         => $g['quantity'],
                        'price'            => $g['price'],
                        'amount'           => $g['amount'],
                        'tax_rate'         => $g['tax_rate'],
                        'tax_dollars'      => $g['tax_dollars'],
                    ];
                    $invSubtotalCents += (int) $g['amount_cents'];
                    $invTaxCents      += (int) $g['tax_cents'];
                }
            }

            $invTotalCents = $invSubtotalCents + $invTaxCents;
            $invSubtotal   = $invSubtotalCents / 100;
            $invTax        = round($invTaxCents / 100, 2);
            $total         = round($invTotalCents / 100, 2);

            $isPaid     = ($stageId === 26);
            $amountPaid = $isPaid ? $total : 0;
            $amountDue  = round($total - $amountPaid, 2);

            $invoice = Invoice::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'order_id'          => $order->id,
                'person_id'         => $order->person_id,
                'organisation_id'   => $order->organisation_id,
                'pipeline_id'       => 5,
                'pipeline_stage_id' => $stageId,
                'invoice_id'        => 'INV-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'invoice_number'    => $docNumber,
                'description'       => $this->faker->sentence,
                'reference'         => $this->randomReference(),
                'issue_date'        => Carbon::now()->subDays($this->faker->numberBetween(0, 30))->format($this->dateFormat),
                'due_date'          => Carbon::now()->addDays($this->faker->numberBetween(7, 45))->format($this->dateFormat),
                'currency'          => 'USD',
                // DOLLARS – mutators ×100 → cents stored.
                'subtotal'    => $invSubtotal,
                'tax'         => $invTax,
                'total'       => $total,
                'amount_due'  => $amountDue,
                'amount_paid' => $amountPaid,
                'team_id'           => $order->team_id,
                'user_owner_id'     => $order->user_owner_id,
                'user_assigned_id'  => $order->user_assigned_id,
                'user_created_id'   => $this->randomUserId(),
            ]);

            foreach ($linesToInsert as $line) {
                InvoiceLine::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'invoice_id'       => $invoice->id,
                    'order_product_id' => $line['order_product_id'],
                    'product_id'       => $line['product_id'],
                    'quantity'         => $line['quantity'],
                    // InvoiceLine has mutators ×100 for price / amount / tax_amount. Pass DOLLARS.
                    'price'            => $line['price'],
                    'amount'           => $line['amount'],
                    'tax_rate'         => $line['tax_rate'],
                    'tax_amount'       => $line['tax_dollars'],
                    'currency'         => 'USD',
                    'team_id'          => $invoice->team_id,
                ]);
            }

            $amountPaidCents = (int) round($amountPaid * 100);
            $amountDueCents  = $invTotalCents - $amountPaidCents;
            $this->setMoneyCents($invoice, [
                'subtotal'    => $invSubtotalCents,
                'tax'         => $invTaxCents,
                'total'       => $invTotalCents,
                'amount_paid' => $amountPaidCents,
                'amount_due'  => $amountDueCents,
            ]);

            $docNumber++;
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Create a single standalone (not-from-order) invoice with random line
     * items. Extracted so it can be invoked from the interleaved task loop.
     */
    protected function createStandaloneInvoice(int $docNumber, array $invStageDist, $organisationIds, $peopleIds): void
    {
        $stageId = $this->weightedRandom($invStageDist);
        $lines   = $this->generateLineItems();
        $totals  = $this->summariseLines($lines);

        $isPaid     = ($stageId === 26);
        $amountPaid = $isPaid ? $totals['total'] : 0;
        $amountDue  = round($totals['total'] - $amountPaid, 2);

        $invoice = Invoice::create([
            'external_id'       => Uuid::uuid4()->toString(),
            'person_id'         => $peopleIds->isNotEmpty() ? $peopleIds->random() : null,
            'organisation_id'   => $organisationIds->isNotEmpty() ? $organisationIds->random() : null,
            'pipeline_id'       => 5,
            'pipeline_stage_id' => $stageId,
            'invoice_id'        => 'INV-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
            'invoice_number'    => $docNumber,
            'description'       => $this->faker->sentence,
            'reference'         => $this->randomReference(),
            'issue_date'        => Carbon::now()->subDays($this->faker->numberBetween(0, 60))->format($this->dateFormat),
            'due_date'          => Carbon::now()->addDays($this->faker->numberBetween(7, 45))->format($this->dateFormat),
            'currency'          => 'USD',
            // DOLLARS – mutators ×100 → cents stored.
            'subtotal'    => $totals['subtotal'],
            'tax'         => $totals['tax'],
            'total'       => $totals['total'],
            'amount_due'  => $amountDue,
            'amount_paid' => $amountPaid,
            'team_id'           => $this->randomTeamId(),
            'user_owner_id'     => $this->randomUserId(),
            'user_assigned_id'  => $this->randomUserId(),
            'user_created_id'   => $this->randomUserId(),
        ]);

        foreach ($lines as $line) {
            InvoiceLine::create([
                'external_id' => Uuid::uuid4()->toString(),
                'invoice_id'  => $invoice->id,
                'product_id'  => $line['product_id'],
                'quantity'    => $line['quantity'],
                'price'       => $line['price'],
                'amount'      => $line['amount'],
                'tax_rate'    => $line['tax_rate'],
                'tax_amount'  => $line['tax_dollars'],
                'currency'    => 'USD',
                'team_id'     => $invoice->team_id,
            ]);
        }

        $amountPaidCents = (int) round($amountPaid * 100);
        $amountDueCents  = $totals['total_cents'] - $amountPaidCents;
        $this->setMoneyCents($invoice, [
            'subtotal'    => $totals['subtotal_cents'],
            'tax'         => $totals['tax_cents'],
            'total'       => $totals['total_cents'],
            'amount_paid' => $amountPaidCents,
            'amount_due'  => $amountDueCents,
        ]);
    }

    /* ----------------------------------------------------------------- */
    /* Deliveries                                                        */
    /* ----------------------------------------------------------------- */

    protected function seedDeliveries(): void
    {
        if (! Schema::hasTable(config('laravel-crm.db_table_prefix').'deliveries')) {
            return;
        }

        // Delivery pipeline: 27 Draft 10%, 28 Packed 15%, 29 Sent 25%, 30 Delivered 50%
        $delivStageDist = [
            10  => 27,  // Draft
            25  => 28,  // Packed
            50  => 29,  // Sent
            100 => 30,  // Delivered
        ];

        $deliveriesTable = config('laravel-crm.db_table_prefix').'deliveries';
        $hasDateCols     = Schema::hasColumn($deliveriesTable, 'delivery_initiated')
            && Schema::hasColumn($deliveriesTable, 'delivery_shipped')
            && Schema::hasColumn($deliveriesTable, 'delivery_expected')
            && Schema::hasColumn($deliveriesTable, 'delivered_on');

        $orders = Order::with(['orderProducts', 'organisation', 'person'])->get();
        $bar    = $this->progressBar($orders->count());

        foreach ($orders as $order) {
            // ~65% of orders get a delivery.
            if (! $this->faker->boolean(65)) {
                $bar && $bar->advance();

                continue;
            }

            $stageId = $this->weightedRandom($delivStageDist);

            // Build the date set based on stage progression. The Delivery
            // model's setDeliveryExpectedAttribute / setDeliveredOnAttribute
            // mutators run Carbon::createFromFormat($dateFormat, $value), so
            // every value MUST be passed as a date string in $this->dateFormat
            // (passing a Carbon instance throws "Trailing data").
            $dateAttrs = [];
            if ($hasDateCols) {
                $now       = Carbon::now();
                $initiated = null;
                $shipped   = null;
                $expected  = null;
                $delivered = null;

                if ($stageId >= 28) { // Packed or beyond → was initiated
                    $initiated = (clone $now)->subDays($this->faker->numberBetween(2, 14));
                }
                if ($stageId >= 29) { // Sent or Delivered → has been shipped
                    $base    = $initiated
                        ? (clone $initiated)
                        : (clone $now)->subDays($this->faker->numberBetween(1, 10));
                    $shipped = (clone $base)
                        ->addDays($this->faker->numberBetween(0, 2))
                        ->addHours($this->faker->numberBetween(1, 8));
                }
                if ($stageId >= 28) { // Anything past Draft → has an expected date
                    $shipBase = $shipped ?? $initiated ?? $now;
                    $expected = (clone $shipBase)->addDays($this->faker->numberBetween(2, 7));
                }
                if ($stageId === 30) { // Delivered → recorded delivery date
                    $delivered = (clone $expected)->addDays($this->faker->numberBetween(-1, 3));
                }

                if ($initiated) {
                    $dateAttrs['delivery_initiated'] = $initiated->format($this->dateFormat);
                }
                if ($shipped) {
                    $dateAttrs['delivery_shipped'] = $shipped->format($this->dateFormat);
                }
                if ($expected) {
                    $dateAttrs['delivery_expected'] = $expected->format($this->dateFormat);
                }
                if ($delivered) {
                    $dateAttrs['delivered_on'] = $delivered->format($this->dateFormat);
                }
            }

            $delivery = Delivery::create(array_merge([
                'external_id'       => Uuid::uuid4()->toString(),
                'order_id'          => $order->id,
                'pipeline_id'       => 6,
                'pipeline_stage_id' => $stageId,
                'team_id'           => $order->team_id,
                'user_owner_id'     => $order->user_owner_id,
                'user_assigned_id'  => $order->user_assigned_id,
                'user_created_id'   => $this->randomUserId(),
            ], $dateAttrs));

            // Shipping address: prefer to copy the parent organisation's
            // existing shipping address (type 6); fall back to the
            // organisation's billing address; otherwise fabricate one.
            $this->attachDeliveryShippingAddress($delivery, $order);

            // Attach a DeliveryProduct row for each OrderProduct on the parent
            // order so Order::deliveryComplete() returns true.
            foreach ($order->orderProducts as $op) {
                DeliveryProduct::create([
                    'external_id'      => Uuid::uuid4()->toString(),
                    'delivery_id'      => $delivery->id,
                    'order_product_id' => $op->id,
                    'quantity'         => $op->quantity,
                    'team_id'          => $delivery->team_id,
                ]);
            }

            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Attach a shipping address (address_type_id = 6) to the given delivery,
     * cloning from the parent order's organisation/person where possible and
     * falling back to fabricated faker data so every delivery has one.
     */
    protected function attachDeliveryShippingAddress(Delivery $delivery, Order $order): void
    {
        $this->createAddressFor(
            $delivery,
            6, // Shipping
            $order->organisation,
            $order->person
        );
    }

    /**
     * Attach a delivery (shipping) address to a PurchaseOrder when its
     * delivery_type is 'deliver'. Sources from the PO's linked organisation
     * or person via createAddressFor(), falling back to faker data.
     */
    protected function attachPurchaseOrderDeliveryAddress(PurchaseOrder $po): void
    {
        if ($po->delivery_type !== 'deliver') {
            return;
        }

        $org = $po->organisation_id
            ? Organisation::with('addresses')->find($po->organisation_id)
            : null;
        $person = $po->person_id
            ? Person::with('addresses')->find($po->person_id)
            : null;

        $this->createAddressFor($po, 6, $org, $person); // Shipping / delivery address
    }

    /**
     * Attach an address of the given type to any addressable model, sourcing
     * the line/city/state/code/country from a related organisation or person
     * when one is available, and falling back to fabricated faker data so
     * we always end up with a populated row.
     *
     * Address type IDs (from LaravelCrmTablesSeeder):
     *   1 Current, 2 Previous, 3 Postal, 4 Business, 5 Billing, 6 Shipping
     *
     * Source-address preference for $sourceOrg:
     *   - exact-type match (e.g. type 6 → org's shipping address)
     *   - billing (type 5) as a sane fallback
     *   - any address on the organisation
     * Then falls through to the person's first address, then faker.
     */
    protected function createAddressFor(
        $host,
        int $typeId,
        ?Organisation $sourceOrg = null,
        ?Person $sourcePerson = null,
        int $primary = 1
    ): void {
        $source = null;

        if ($sourceOrg) {
            $source = $sourceOrg->addresses()
                ->where('address_type_id', $typeId)->first()
                ?? $sourceOrg->addresses()
                    ->where('address_type_id', 5)->first()
                ?? $sourceOrg->addresses()->first();
        }

        if (! $source && $sourcePerson) {
            $source = $sourcePerson->addresses()->first();
        }

        $payload = $source
            ? [
                'line1'   => $source->line1,
                'line2'   => $source->line2,
                'line3'   => $source->line3,
                'city'    => $source->city,
                'state'   => $source->state,
                'code'    => $source->code,
                'country' => $source->country,
            ]
            : [
                'line1'   => $this->faker->streetAddress,
                'city'    => $this->faker->city,
                'state'   => $this->faker->stateAbbr,
                'code'    => $this->faker->postcode,
                'country' => $this->faker->country,
            ];

        Address::create(array_merge([
            'external_id'      => Uuid::uuid4()->toString(),
            'addressable_type' => get_class($host),
            'addressable_id'   => $host->id,
            'address_type_id'  => $typeId,
            'primary'          => $primary,
            'team_id'          => $host->team_id,
        ], $payload));
    }

    /**
     * Attach a billing (type 5, primary) and shipping (type 6, non-primary)
     * address to every order. Sources from the order's parent organisation
     * /person; if no related contact exists, fabricates faker data so the
     * order is never left without addresses.
     */
    protected function attachOrderAddresses(Order $order): void
    {
        $orgForAddr = $order->organisation_id
            ? Organisation::with('addresses')->find($order->organisation_id)
            : null;
        $personForAddr = $order->person_id
            ? Person::with('addresses')->find($order->person_id)
            : null;

        $this->createAddressFor($order, 5, $orgForAddr, $personForAddr);    // Billing  (primary)
        $this->createAddressFor($order, 6, $orgForAddr, $personForAddr, 0); // Shipping (non-primary)
    }

    /* ----------------------------------------------------------------- */
    /* Purchase Orders                                                   */
    /* ----------------------------------------------------------------- */

    protected function seedPurchaseOrders(): void
    {
        // PO pipeline: 31 Draft 15%, 32 Awaiting Approval 15%,
        //              33 Approved 35%, 34 Paid 35%
        $poStageDist = [
            15  => 31,  // Draft
            30  => 32,  // Awaiting Approval
            65  => 33,  // Approved
            100 => 34,  // Paid
        ];

        $docNumber = 11000;

        // ── Build a combined, interleaved task list ──────────────────────
        // ~45% of orders get a PO, mixed in with standalone POs so the
        // numbering / created_at order is interleaved.
        $orders = Order::with('orderProducts')->get();

        $tasks = [];
        foreach ($orders as $order) {
            if ($this->faker->boolean(45)) {
                $tasks[] = ['type' => 'order', 'order' => $order];
            }
        }

        $standaloneCount = max(40, (int) ((count($tasks) + 1) * 0.25));
        for ($i = 0; $i < $standaloneCount; $i++) {
            $tasks[] = ['type' => 'standalone'];
        }

        shuffle($tasks);

        $organisationIds = Organisation::pluck('id');
        $peopleIds       = Person::pluck('id');

        $bar = $this->progressBar(count($tasks));

        foreach ($tasks as $task) {
            if ($task['type'] !== 'order') {
                $this->createStandalonePurchaseOrder($docNumber, $poStageDist, $organisationIds, $peopleIds);
                $docNumber++;
                $bar && $bar->advance();

                continue;
            }

            $order = $task['order'];

            // Build PO lines from the order's products at COST prices.
            $this->ensureProductPricing();
            $poSubtotalCents = 0;
            $poTaxCents      = 0;
            $linesToInsert   = [];

            foreach ($order->orderProducts as $op) {
                $info = $this->productPricing[$op->product_id] ?? null;
                if (! $info) {
                    continue;
                }
                $costPrice    = $info['cost'];
                $quantity     = $op->quantity ?: 1;
                $amount       = $quantity * $costPrice;
                $rate         = $info['tax_rate'];
                $priceCents   = (int) ($costPrice * 100);
                $amountCents  = $quantity * $priceCents;
                $taxCentsLine = (int) round($amountCents * ($rate / 100));
                $taxDollars   = round($taxCentsLine / 100, 2);

                $linesToInsert[] = [
                    'product_id'  => $op->product_id,
                    'quantity'    => $quantity,
                    'price'       => $costPrice,
                    'amount'      => $amount,
                    'tax_rate'    => $rate,
                    'tax_dollars' => $taxDollars,
                ];

                $poSubtotalCents += $amountCents;
                $poTaxCents      += $taxCentsLine;
            }

            if (empty($linesToInsert)) {
                $generated = $this->generateLineItems(null, true);
                foreach ($generated as $g) {
                    $linesToInsert[] = [
                        'product_id'  => $g['product_id'],
                        'quantity'    => $g['quantity'],
                        'price'       => $g['price'],
                        'amount'      => $g['amount'],
                        'tax_rate'    => $g['tax_rate'],
                        'tax_dollars' => $g['tax_dollars'],
                    ];
                    $poSubtotalCents += (int) $g['amount_cents'];
                    $poTaxCents      += (int) $g['tax_cents'];
                }
            }

            $poTotalCents = $poSubtotalCents + $poTaxCents;
            $poSubtotal   = $poSubtotalCents / 100;
            $poTax        = round($poTaxCents / 100, 2);
            $total        = round($poTotalCents / 100, 2);

            // 80% of POs are delivered, 20% are picked up. The supplier
            // "contact" is the person_id / organisation_id pair – inherited
            // from the parent order so it's always populated for converted
            // POs. Delivery instructions add realism for delivered POs.
            $deliveryType = $this->faker->boolean(80) ? 'deliver' : 'pickup';

            $purchaseOrder = PurchaseOrder::create([
                'external_id'        => Uuid::uuid4()->toString(),
                'order_id'           => $order->id,
                'person_id'          => $order->person_id,
                'organisation_id'    => $order->organisation_id,
                'pipeline_id'        => 7,
                'pipeline_stage_id'  => $this->weightedRandom($poStageDist),
                'purchase_order_id'  => 'PO-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'number'             => $docNumber,
                'reference'          => $this->randomReference(true),
                'issue_date'         => Carbon::now()->subDays($this->faker->numberBetween(0, 14))->format($this->dateFormat),
                'delivery_date'      => Carbon::now()->addDays($this->faker->numberBetween(7, 30))->format($this->dateFormat),
                'delivery_type'      => $deliveryType,
                'delivery_instructions' => $deliveryType === 'deliver'
                    ? $this->faker->randomElement([
                        'Leave with reception if no answer.',
                        'Use loading dock at rear of building.',
                        'Call recipient on arrival.',
                        'Deliver between 9am and 4pm only.',
                        'Sign required on delivery.',
                    ])
                    : null,
                'currency'           => 'USD',
                // DOLLARS – mutators ×100 → cents stored.
                'subtotal' => $poSubtotal,
                'tax'      => $poTax,
                'total'    => $total,
                'team_id'            => $order->team_id,
                'user_owner_id'      => $order->user_owner_id,
                'user_assigned_id'   => $order->user_assigned_id,
                'user_created_id'    => $this->randomUserId(),
            ]);

            foreach ($linesToInsert as $line) {
                PurchaseOrderLine::create([
                    'external_id'       => Uuid::uuid4()->toString(),
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $line['product_id'],
                    'quantity'          => $line['quantity'],
                    // PurchaseOrderLine has mutators ×100 for price / amount / tax_amount. Pass DOLLARS.
                    'price'             => $line['price'],
                    'amount'            => $line['amount'],
                    'tax_rate'          => $line['tax_rate'],
                    'tax_amount'        => $line['tax_dollars'],
                    'currency'          => 'USD',
                    'team_id'           => $purchaseOrder->team_id,
                ]);
            }

            $this->setMoneyCents($purchaseOrder, [
                'subtotal' => $poSubtotalCents,
                'tax'      => $poTaxCents,
                'total'    => $poTotalCents,
            ]);

            // Delivery address (only for delivered POs – pickups don't need
            // one). Sourced from the parent order's organisation/person via
            // the same helper used for orders & deliveries.
            $this->attachPurchaseOrderDeliveryAddress($purchaseOrder);

            $docNumber++;
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Create a single standalone (not-from-order) purchase order with random
     * line items. Extracted so it can be invoked from the interleaved task
     * loop.
     */
    protected function createStandalonePurchaseOrder(int $docNumber, array $poStageDist, $organisationIds, $peopleIds): void
    {
        $lines  = $this->generateLineItems(null, true);
        $totals = $this->summariseLines($lines);

        // 80% delivered / 20% pickup. Standalone POs always carry both
        // person_id and organisation_id so the supplier "contact" is
        // populated end-to-end.
        $deliveryType    = $this->faker->boolean(80) ? 'deliver' : 'pickup';
        $organisationId  = $organisationIds->isNotEmpty() ? $organisationIds->random() : null;
        $personId        = $peopleIds->isNotEmpty() ? $peopleIds->random() : null;

        $purchaseOrder = PurchaseOrder::create([
            'external_id'       => Uuid::uuid4()->toString(),
            'person_id'         => $personId,
            'organisation_id'   => $organisationId,
            'pipeline_id'       => 7,
            'pipeline_stage_id' => $this->weightedRandom($poStageDist),
            'purchase_order_id' => 'PO-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
            'number'            => $docNumber,
            'reference'         => $this->randomReference(true),
            'issue_date'        => Carbon::now()->subDays($this->faker->numberBetween(0, 30))->format($this->dateFormat),
            'delivery_date'     => Carbon::now()->addDays($this->faker->numberBetween(7, 45))->format($this->dateFormat),
            'delivery_type'     => $deliveryType,
            'delivery_instructions' => $deliveryType === 'deliver'
                ? $this->faker->randomElement([
                    'Leave with reception if no answer.',
                    'Use loading dock at rear of building.',
                    'Call recipient on arrival.',
                    'Deliver between 9am and 4pm only.',
                    'Sign required on delivery.',
                ])
                : null,
            'currency'          => 'USD',
            // DOLLARS – mutators ×100 → cents stored.
            'subtotal' => $totals['subtotal'],
            'tax'      => $totals['tax'],
            'total'    => $totals['total'],
            'team_id'           => $this->randomTeamId(),
            'user_owner_id'     => $this->randomUserId(),
            'user_assigned_id'  => $this->randomUserId(),
            'user_created_id'   => $this->randomUserId(),
        ]);

        foreach ($lines as $line) {
            PurchaseOrderLine::create([
                'external_id'       => Uuid::uuid4()->toString(),
                'purchase_order_id' => $purchaseOrder->id,
                'product_id'        => $line['product_id'],
                'quantity'          => $line['quantity'],
                'price'             => $line['price'],
                'amount'            => $line['amount'],
                'tax_rate'          => $line['tax_rate'],
                'tax_amount'        => $line['tax_dollars'],
                'currency'          => 'USD',
                'team_id'           => $purchaseOrder->team_id,
            ]);
        }

        $this->setMoneyCents($purchaseOrder, [
            'subtotal' => $totals['subtotal_cents'],
            'tax'      => $totals['tax_cents'],
            'total'    => $totals['total_cents'],
        ]);

        // Attach a delivery address when the PO is being delivered.
        $this->attachPurchaseOrderDeliveryAddress($purchaseOrder);
    }

    /* ----------------------------------------------------------------- */
    /* Activities                                                        */
    /* ----------------------------------------------------------------- */

    protected function seedActivities(): void
    {
        // Each of these entity types must end up with Notes, Tasks, Calls,
        // Meetings and Lunches attached *directly* (via the activity model's
        // own morph columns – noteable_*, taskable_*, callable_*,
        // meetingable_*, lunchable_*) so detail pages always show a populated
        // timeline.  We process them one type at a time so the per-entity
        // coverage is obvious and easy to extend.
        $entitySources = [
            'Leads'           => Lead::cursor(),
            'Deals'           => Deal::cursor(),
            'Quotes'          => Quote::cursor(),
            'Orders'          => Order::cursor(),
            'Invoices'        => Invoice::cursor(),
            'Deliveries'      => Delivery::cursor(),
            'Purchase Orders' => PurchaseOrder::cursor(),
            'Customers'       => Client::cursor(),
            'People'          => Person::cursor(),
            'Organisations'   => Organisation::cursor(),
        ];

        $totalCounts = [
            'Leads'           => Lead::count(),
            'Deals'           => Deal::count(),
            'Quotes'          => Quote::count(),
            'Orders'          => Order::count(),
            'Invoices'        => Invoice::count(),
            'Deliveries'      => Delivery::count(),
            'Purchase Orders' => PurchaseOrder::count(),
            'Customers'       => Client::count(),
            'People'          => Person::count(),
            'Organisations'   => Organisation::count(),
        ];

        foreach ($entitySources as $label => $cursor) {
            $count = $totalCounts[$label] ?? 0;
            if ($count === 0) {
                continue;
            }

            $this->writeln(sprintf('  · %s: seeding activities (%d)', $label, $count));
            $bar = $this->progressBar($count);

            foreach ($cursor as $host) {
                $this->seedActivitiesForHost($host);
                $bar && $bar->advance();
            }

            if ($bar) {
                $bar->finish();
                $this->writeln('');
            }
        }
    }

    /**
     * Create 2–4 of each activity type (Task, Note, Call, Meeting, Lunch)
     * directly attached to the given host via that activity's own morph
     * columns, plus a matching timeline `activities` row per host.
     */
    protected function seedActivitiesForHost($host): void
    {
        $minPerType = 2;
        $maxPerType = 4;

        // Tasks – taskable_type / taskable_id → host
        for ($i = 0; $i < $this->faker->numberBetween($minPerType, $maxPerType); $i++) {
            $task = Task::create($this->activityAttributes($host, 'taskable', [
                'name'        => $this->faker->sentence(4),
                'description' => $this->faker->paragraph,
                'due_at'      => Carbon::now()->addDays($this->faker->numberBetween(1, 60))
                                     ->setTime($this->faker->numberBetween(9, 16), 0)
                                     ->format($this->dateTimeFormat),
            ]));
            $this->recordActivity($host, $task);
        }

        // Notes – noteable_type / noteable_id → host
        for ($i = 0; $i < $this->faker->numberBetween($minPerType, $maxPerType); $i++) {
            $note = Note::create([
                'external_id'     => Uuid::uuid4()->toString(),
                'content'         => $this->faker->paragraph,
                'noteable_type'   => get_class($host),
                'noteable_id'     => $host->id,
                'pinned'          => $this->faker->boolean(20),
                'team_id'         => $host->team_id ?? $this->randomTeamId(),
                'user_created_id' => $this->randomUserId(),
            ]);
            $this->recordActivity($host, $note);
        }

        // Calls – callable_type / callable_id → host
        for ($i = 0; $i < $this->faker->numberBetween($minPerType, $maxPerType); $i++) {
            $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))
                ->setTime($this->faker->numberBetween(9, 16), 0);
            $call = Call::create($this->activityAttributes($host, 'callable', [
                'name'        => 'Call - '.$this->faker->sentence(3),
                'description' => $this->faker->sentence,
                'start_at'    => $start->format($this->dateTimeFormat),
                'finish_at'   => (clone $start)->addMinutes(30)->format($this->dateTimeFormat),
            ]));
            $this->recordActivity($host, $call);
        }

        // Meetings – meetingable_type / meetingable_id → host
        for ($i = 0; $i < $this->faker->numberBetween($minPerType, $maxPerType); $i++) {
            $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))
                ->setTime($this->faker->numberBetween(9, 16), 0);
            $meeting = Meeting::create($this->activityAttributes($host, 'meetingable', [
                'name'        => 'Meeting - '.$this->faker->sentence(3),
                'description' => $this->faker->sentence,
                'start_at'    => $start->format($this->dateTimeFormat),
                'finish_at'   => (clone $start)->addHour()->format($this->dateTimeFormat),
            ]));
            $this->recordActivity($host, $meeting);
        }

        // Lunches – lunchable_type / lunchable_id → host
        for ($i = 0; $i < $this->faker->numberBetween($minPerType, $maxPerType); $i++) {
            $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))->setTime(12, 0);
            $lunch = Lunch::create($this->activityAttributes($host, 'lunchable', [
                'name'        => 'Lunch with '.($host->name ?? $host->first_name ?? 'contact'),
                'description' => $this->faker->sentence,
                'start_at'    => $start->format($this->dateTimeFormat),
                'finish_at'   => (clone $start)->addHour()->format($this->dateTimeFormat),
            ]));
            $this->recordActivity($host, $lunch);
        }
    }

    protected function activityAttributes($host, string $morphName, array $extra = []): array
    {
        return array_merge([
            'external_id'        => Uuid::uuid4()->toString(),
            "{$morphName}_type"  => get_class($host),
            "{$morphName}_id"    => $host->id,
            'team_id'            => $host->team_id ?? $this->randomTeamId(),
            'user_owner_id'      => $this->randomUserId(),
            'user_assigned_id'   => $this->randomUserId(),
            'user_created_id'    => $this->randomUserId(),
        ], $extra);
    }

    protected function recordActivity($host, $record, string $event = 'created'): void
    {
        $userId    = $this->randomUserId();
        $userClass = config('auth.providers.users.model', \App\User::class);

        Activity::create([
            'external_id'       => Uuid::uuid4()->toString(),
            'log_name'          => 'default',
            'description'       => $event,
            'event'             => $event,
            'causeable_type'    => $userId ? $userClass : null,
            'causeable_id'      => $userId,
            'timelineable_type' => get_class($host),
            'timelineable_id'   => $host->id,
            'recordable_type'   => get_class($record),
            'recordable_id'     => $record->id,
        ]);
    }

    /* ----------------------------------------------------------------- */
    /* Custom field values                                               */
    /* ----------------------------------------------------------------- */

    protected function applyCustomFieldValues(): void
    {
        $fields = Field::with('fieldOptions')->get();
        if ($fields->isEmpty()) {
            return;
        }

        $hostsByModel = [
            Lead::class         => Lead::inRandomOrder()->limit(150)->get(),
            Deal::class         => Deal::inRandomOrder()->limit(150)->get(),
            Quote::class        => Quote::inRandomOrder()->limit(100)->get(),
            Order::class        => Order::inRandomOrder()->limit(100)->get(),
            Person::class       => Person::inRandomOrder()->limit(200)->get(),
            Organisation::class => Organisation::inRandomOrder()->limit(200)->get(),
            Product::class      => Product::inRandomOrder()->limit(100)->get(),
        ];

        $bar = $this->progressBar($fields->count());

        foreach ($fields as $field) {
            $appliesTo = FieldModel::where('field_id', $field->id)->pluck('model');

            foreach ($appliesTo as $modelClass) {
                if (! isset($hostsByModel[$modelClass])) {
                    continue;
                }

                foreach ($hostsByModel[$modelClass] as $host) {
                    if (! $this->faker->boolean(60)) {
                        continue;
                    }

                    FieldValue::create([
                        'external_id'            => Uuid::uuid4()->toString(),
                        'field_id'               => $field->id,
                        'field_valueable_type'   => $modelClass,
                        'field_valueable_id'     => $host->id,
                        'value'                  => $this->generateFieldValue($field),
                    ]);
                }
            }

            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    protected function generateFieldValue(Field $field): string
    {
        switch ($field->type) {
            case 'checkbox':
                return $this->faker->boolean ? '1' : '0';
            case 'date':
                return Carbon::now()->addDays($this->faker->numberBetween(-30, 90))->toDateString();
            case 'select':
            case 'radio':
                $option = $field->fieldOptions->isNotEmpty() ? $field->fieldOptions->random() : null;

                return $option ? $option->value : '';
            case 'select_multiple':
                if ($field->fieldOptions->isEmpty()) {
                    return json_encode([]);
                }
                $count = min(2, $field->fieldOptions->count());

                return json_encode($field->fieldOptions->random($count)->pluck('value')->all());
            case 'textarea':
                return $this->faker->paragraph;
            case 'text':
            default:
                return $this->faker->sentence(3);
        }
    }

    /* ----------------------------------------------------------------- */
    /* Helpers                                                           */
    /* ----------------------------------------------------------------- */

    protected function randomUserId(): ?int
    {
        return $this->userIds->isEmpty() ? null : $this->userIds->random();
    }

    protected function randomTeamId(): ?int
    {
        if (! isset($this->teamIds) || $this->teamIds->isEmpty()) {
            return null;
        }

        return $this->teamIds->random();
    }

    /**
     * Pick a value from a weighted distribution map.
     *
     * Keys are cumulative percentage thresholds (1–100); values are what to
     * return, e.g.:
     *
     *   [20 => 'a', 50 => 'b', 100 => 'c']
     *   → 20% 'a', 30% 'b', 50% 'c'
     *
     * @param  array<int, mixed>  $distribution
     * @return mixed
     */
    protected function weightedRandom(array $distribution)
    {
        $roll = mt_rand(1, 100);

        foreach ($distribution as $threshold => $value) {
            if ($roll <= $threshold) {
                return $value;
            }
        }

        return end($distribution);
    }
}
