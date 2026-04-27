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
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Task;
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
     * Run the database seeds.
     *
     * Designed to be safe to run repeatedly (and after `migrate:fresh --seed`):
     *  - Existing users are NEVER deleted; new sample users are added with
     *    firstOrCreate so any users already present are preserved.
     *  - Other records are added cumulatively – run again to grow the dataset.
     *
     * @return void
     */
    public function run()
    {
        $this->faker = \Faker\Factory::create();

        // Several CRM models define `set*Attribute` mutators that re-parse
        // string values via Carbon::createFromFormat($model::dateFormat(), ...).
        // Resolve the configured formats once so we can hand the models
        // strings shaped exactly as they expect.
        $this->dateFormat = Deal::dateFormat();
        $this->dateTimeFormat = $this->dateFormat.' H:i';

        DB::transaction(function () {
            $this->step('Users', fn () => $this->seedUsers());
            $this->step('Teams', fn () => $this->seedTeams());
            $this->step('Product catalogue', fn () => $this->seedProductCatalogue());
            $this->step('Custom fields', fn () => $this->seedCustomFields());
            $this->step('Organisations & people', fn () => $this->seedOrganisationsAndPeople());
            $this->step('Clients', fn () => $this->seedClients());
            $this->step('Leads & deals', fn () => $this->seedLeadsAndDeals());
            $this->step('Sales documents', fn () => $this->seedSalesDocuments());
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
        $this->writeln(sprintf('  Clients:         %d', Client::count()));
        $this->writeln(sprintf('  Products:        %d', Product::count()));
        $this->writeln(sprintf('  Leads:           %d', Lead::count()));
        $this->writeln(sprintf('  Deals:           %d', Deal::count()));
        $this->writeln(sprintf('  Quotes:          %d', Quote::count()));
        $this->writeln(sprintf('  Orders:          %d', Order::count()));
        $this->writeln(sprintf('  Invoices:        %d', Invoice::count()));
        $this->writeln(sprintf('  Purchase orders: %d', PurchaseOrder::count()));
        $this->writeln(sprintf('  Tasks:           %d', Task::count()));
        $this->writeln(sprintf('  Notes:           %d', Note::count()));
        $this->writeln(sprintf('  Calls:           %d', Call::count()));
        $this->writeln(sprintf('  Meetings:        %d', Meeting::count()));
        $this->writeln(sprintf('  Lunches:         %d', Lunch::count()));
        $this->writeln(sprintf('  Activities:      %d', Activity::count()));
        $this->writeln(sprintf('  Custom fields:   %d', Field::count()));
        $this->writeln(sprintf('  Field values:    %d', FieldValue::count()));
    }

    /**
     * Run a single seeding step with a header & timing.
     */
    protected function step(string $label, callable $callback): void
    {
        $this->writeln('');
        $this->writeln(sprintf('▶ %s …', $label));

        $started = microtime(true);
        $callback();
        $elapsed = number_format(microtime(true) - $started, 2);

        $this->writeln(sprintf('  ✓ Done (%.2fs)', $elapsed));
    }

    /**
     * Write a line via the bound console command (if any).
     */
    protected function writeln(string $message): void
    {
        if (isset($this->command)) {
            $this->command->getOutput()->writeln($message);
        }
    }

    /**
     * Create a fresh Symfony progress bar bound to the console command.
     *
     * @return \Symfony\Component\Console\Helper\ProgressBar|null
     */
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

    /* ----------------------------------------------------------------- */
    /* Users                                                             */
    /* ----------------------------------------------------------------- */

    protected function seedUsers(): void
    {
        $userClass = config('auth.providers.users.model', \App\User::class);

        // IMPORTANT: do NOT delete or truncate the users table here. We use
        // firstOrCreate so re-running this seeder (including via
        // `migrate:fresh --seed` where the host application has its own user
        // seeder) preserves any already-present users.
        $sampleUsers = [
            ['name' => 'Sample Owner',   'email' => 'owner@sample.test'],
            ['name' => 'Sample Manager', 'email' => 'manager@sample.test'],
            ['name' => 'Sample Sales',   'email' => 'sales@sample.test'],
            ['name' => 'Sample Support', 'email' => 'support@sample.test'],
        ];

        $created = collect();

        foreach ($sampleUsers as $data) {
            $user = $userClass::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $created->push($user->id);
        }

        // Build the full pool of usable user IDs (existing + sample) for
        // owner/assignee fields throughout the dataset.
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
            $team = Team::firstOrCreate(
                ['name' => $name],
                ['user_id' => $ownerId]
            );

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

        for ($i = 0; $i < 200; $i++) {
            Product::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $this->faker->unique()->words(2, true),
                'description' => $this->faker->sentence,
                'product_category_id' => $categoryIds->random(),
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
            ]);
        }
    }

    /* ----------------------------------------------------------------- */
    /* Custom field groups & fields                                      */
    /* ----------------------------------------------------------------- */

    protected function seedCustomFields(): void
    {
        $blueprint = [
            [
                'group' => 'Marketing Profile',
                'models' => [Lead::class, Deal::class],
                'fields' => [
                    ['name' => 'Source Channel',  'handle' => 'source_channel',  'type' => 'select',   'options' => ['Web', 'Referral', 'Event', 'Cold Outreach']],
                    ['name' => 'Campaign Code',   'handle' => 'campaign_code',   'type' => 'text'],
                    ['name' => 'Marketing Notes', 'handle' => 'marketing_notes', 'type' => 'textarea'],
                ],
            ],
            [
                'group' => 'Sales Qualification',
                'models' => [Lead::class, Deal::class, Quote::class, Order::class],
                'fields' => [
                    ['name' => 'Budget Confirmed', 'handle' => 'budget_confirmed', 'type' => 'checkbox'],
                    ['name' => 'Decision Date',    'handle' => 'decision_date',    'type' => 'date'],
                    ['name' => 'Priority',         'handle' => 'priority',         'type' => 'radio', 'options' => ['Low', 'Medium', 'High']],
                ],
            ],
            [
                'group' => 'Contact Profile',
                'models' => [Person::class, Organisation::class],
                'fields' => [
                    ['name' => 'LinkedIn URL',      'handle' => 'linkedin_url',      'type' => 'text'],
                    ['name' => 'Preferred Contact', 'handle' => 'preferred_contact', 'type' => 'select',          'options' => ['Email', 'Phone', 'SMS']],
                    ['name' => 'Languages',         'handle' => 'languages',         'type' => 'select_multiple', 'options' => ['English', 'Spanish', 'French', 'German']],
                ],
            ],
            [
                'group' => 'Product Specification',
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
                    'name' => $groupSpec['group'],
                    'team_id' => $this->randomTeamId(),
                ]
            );

            foreach ($groupSpec['fields'] as $fieldSpec) {
                $field = Field::firstOrCreate(
                    ['field_group_id' => $group->id, 'handle' => $fieldSpec['handle']],
                    [
                        'external_id' => Uuid::uuid4()->toString(),
                        'name' => $fieldSpec['name'],
                        'type' => $fieldSpec['type'],
                        'team_id' => $group->team_id,
                        'required' => false,
                    ]
                );

                foreach ($groupSpec['models'] as $modelClass) {
                    FieldModel::firstOrCreate(
                        ['field_id' => $field->id, 'model' => $modelClass],
                        [
                            'external_id' => Uuid::uuid4()->toString(),
                            'team_id' => $group->team_id,
                        ]
                    );
                }

                if (! empty($fieldSpec['options'])) {
                    foreach ($fieldSpec['options'] as $order => $option) {
                        FieldOption::firstOrCreate(
                            ['field_id' => $field->id, 'value' => Str::slug($option, '_')],
                            [
                                'external_id' => Uuid::uuid4()->toString(),
                                'label' => $option,
                                'order' => $order,
                                'team_id' => $group->team_id,
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
            Organisation::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $this->faker->company,
                'description' => $this->faker->catchPhrase,
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
            ]);

            $bar && $bar->advance();
        }

        $organisationIds = Organisation::pluck('id');

        for ($i = 0; $i < 1200; $i++) {
            Person::create([
                'external_id' => Uuid::uuid4()->toString(),
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'title' => $this->faker->randomElement(['Mr', 'Ms', 'Dr', null]),
                'description' => $this->faker->sentence,
                'organisation_id' => $organisationIds->isNotEmpty() && $this->faker->boolean(80)
                    ? $organisationIds->random()
                    : null,
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
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
    /* Clients (customers)                                               */
    /* ----------------------------------------------------------------- */

    protected function seedClients(): void
    {
        $organisations = Organisation::inRandomOrder()->limit(200)->get();
        $people = Person::inRandomOrder()->limit(150)->get();

        $bar = $this->progressBar($organisations->count() + $people->count());

        foreach ($organisations as $organisation) {
            Client::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $organisation->name,
                'clientable_type' => Organisation::class,
                'clientable_id' => $organisation->id,
                'team_id' => $organisation->team_id,
                'user_owner_id' => $organisation->user_owner_id,
                'user_created_id' => $this->randomUserId(),
            ]);
            $bar && $bar->advance();
        }

        foreach ($people as $person) {
            Client::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => trim($person->first_name.' '.$person->last_name),
                'clientable_type' => Person::class,
                'clientable_id' => $person->id,
                'team_id' => $person->team_id,
                'user_owner_id' => $person->user_owner_id,
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
    /* Leads & Deals                                                     */
    /* ----------------------------------------------------------------- */

    protected function seedLeadsAndDeals(): void
    {
        $organisationIds = Organisation::pluck('id');
        $peopleIds = Person::pluck('id');

        $bar = $this->progressBar(400);

        for ($i = 0; $i < 400; $i++) {
            Lead::create([
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $this->faker->catchPhrase,
                'description' => $this->faker->paragraph,
                // Lead's setAmountAttribute mutator multiplies by 100 to
                // store cents – pass the raw dollar value here.
                'amount' => $this->faker->numberBetween(100, 100000),
                'currency' => 'USD',
                'person_id' => $peopleIds->isNotEmpty() ? $peopleIds->random() : null,
                'organisation_id' => $organisationIds->isNotEmpty() ? $organisationIds->random() : null,
                'expected_close' => Carbon::now()->addDays($this->faker->numberBetween(7, 90))->format($this->dateFormat),
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
                'user_assigned_id' => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
                'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 60)),
            ]);
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }

        $leads = Lead::inRandomOrder()->limit(250)->get();
        $bar2 = $this->progressBar($leads->count());

        foreach ($leads as $lead) {
            // Lead's expected_close column is cast to a Carbon instance, so
            // re-format it back into the configured date string before
            // handing it to Deal's setExpectedCloseAttribute mutator.
            $expectedClose = $lead->expected_close
                ? Carbon::parse($lead->expected_close)->format($this->dateFormat)
                : null;

            // The lead's stored amount is already in cents (mutator x 100).
            // Deal's setAmountAttribute will multiply again, so divide back
            // to the raw dollar figure first.
            $dealAmount = $lead->amount !== null ? (int) ($lead->amount / 100) : null;

            Deal::create([
                'external_id' => Uuid::uuid4()->toString(),
                'lead_id' => $lead->id,
                'person_id' => $lead->person_id,
                'organisation_id' => $lead->organisation_id,
                'title' => $lead->title.' (Deal)',
                'description' => $this->faker->paragraph,
                'amount' => $dealAmount,
                'currency' => 'USD',
                'expected_close' => $expectedClose,
                'team_id' => $lead->team_id,
                'user_owner_id' => $lead->user_owner_id,
                'user_assigned_id' => $lead->user_assigned_id,
                'user_created_id' => $this->randomUserId(),
                'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 30)),
            ]);
            $bar2 && $bar2->advance();
        }

        if ($bar2) {
            $bar2->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Quotes / Orders / Invoices / Deliveries / Purchase Orders         */
    /* ----------------------------------------------------------------- */

    protected function seedSalesDocuments(): void
    {
        $deals = Deal::all();
        $docNumber = 1000;

        $bar = $this->progressBar($deals->count());

        foreach ($deals as $deal) {
            $subtotal = $deal->amount ?? $this->faker->numberBetween(1000, 50000) * 100;
            $tax = (int) round($subtotal * 0.10);
            $total = $subtotal + $tax;

            $quote = Quote::create([
                'external_id' => Uuid::uuid4()->toString(),
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'person_id' => $deal->person_id,
                'organisation_id' => $deal->organisation_id,
                'quote_id' => 'Q-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'title' => $deal->title,
                'description' => $this->faker->sentence,
                'issue_at' => Carbon::now()->subDays($this->faker->numberBetween(5, 30))->format($this->dateFormat),
                'expire_at' => Carbon::now()->addDays($this->faker->numberBetween(15, 60))->format($this->dateFormat),
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'team_id' => $deal->team_id,
                'user_owner_id' => $deal->user_owner_id,
                'user_assigned_id' => $deal->user_assigned_id,
                'user_created_id' => $this->randomUserId(),
            ]);

            // Only ~70% of quotes convert into orders.
            if (! $this->faker->boolean(70)) {
                $docNumber++;

                continue;
            }

            $order = Order::create([
                'external_id' => Uuid::uuid4()->toString(),
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'quote_id' => $quote->id,
                'person_id' => $deal->person_id,
                'organisation_id' => $deal->organisation_id,
                'order_id' => 'O-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'number' => $docNumber,
                'description' => $this->faker->sentence,
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'team_id' => $deal->team_id,
                'user_owner_id' => $deal->user_owner_id,
                'user_assigned_id' => $deal->user_assigned_id,
                'user_created_id' => $this->randomUserId(),
            ]);

            Invoice::create([
                'external_id' => Uuid::uuid4()->toString(),
                'order_id' => $order->id,
                'person_id' => $deal->person_id,
                'organisation_id' => $deal->organisation_id,
                'invoice_id' => 'INV-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                'invoice_number' => $docNumber,
                'description' => $this->faker->sentence,
                'issue_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 14))->format($this->dateFormat),
                'due_date' => Carbon::now()->addDays($this->faker->numberBetween(7, 30))->format($this->dateFormat),
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'amount_due' => $total,
                'amount_paid' => 0,
                'team_id' => $deal->team_id,
                'user_owner_id' => $deal->user_owner_id,
                'user_assigned_id' => $deal->user_assigned_id,
                'user_created_id' => $this->randomUserId(),
            ]);

            if (Schema::hasTable(config('laravel-crm.db_table_prefix').'deliveries')) {
                Delivery::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'order_id' => $order->id,
                    'team_id' => $deal->team_id,
                    'user_owner_id' => $deal->user_owner_id,
                    'user_assigned_id' => $deal->user_assigned_id,
                    'user_created_id' => $this->randomUserId(),
                ]);
            }

            if ($this->faker->boolean(50)) {
                PurchaseOrder::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'order_id' => $order->id,
                    'person_id' => $deal->person_id,
                    'organisation_id' => $deal->organisation_id,
                    'purchase_order_id' => 'PO-'.str_pad((string) $docNumber, 6, '0', STR_PAD_LEFT),
                    'number' => $docNumber,
                    'reference' => 'REF-'.$this->faker->numerify('#####'),
                    'issue_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 14))->format($this->dateFormat),
                    'delivery_date' => Carbon::now()->addDays($this->faker->numberBetween(7, 30))->format($this->dateFormat),
                    'currency' => 'USD',
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'team_id' => $deal->team_id,
                    'user_owner_id' => $deal->user_owner_id,
                    'user_assigned_id' => $deal->user_assigned_id,
                    'user_created_id' => $this->randomUserId(),
                ]);
            }

            $docNumber++;
            $bar && $bar->advance();
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /* ----------------------------------------------------------------- */
    /* Tasks / Notes / Calls / Meetings / Lunches                        */
    /* ----------------------------------------------------------------- */

    protected function seedActivities(): void
    {
        // Every entity (lead, deal, person, organisation) gets a guaranteed
        // minimum of activities so the UI always has something to show on
        // each detail page.
        $hosts = collect()
            ->merge(Lead::all())
            ->merge(Deal::all())
            ->merge(Person::all())
            ->merge(Organisation::all());

        if ($hosts->isEmpty()) {
            return;
        }

        $minPerType = 5;
        $bar = $this->progressBar($hosts->count());

        foreach ($hosts as $host) {
            $teamId = $host->team_id ?? $this->randomTeamId();

            // Tasks – 5 to 8 per host.
            for ($i = 0; $i < $this->faker->numberBetween($minPerType, $minPerType + 3); $i++) {
                $task = Task::create($this->activityAttributes($host, 'taskable', [
                    'name' => $this->faker->sentence(4),
                    'description' => $this->faker->paragraph,
                    'due_at' => Carbon::now()->addDays($this->faker->numberBetween(1, 60))
                        ->setTime($this->faker->numberBetween(9, 16), 0)
                        ->format($this->dateTimeFormat),
                ]));
                $this->recordActivity($host, $task);
            }

            // Notes – 5 to 8 per host.
            for ($i = 0; $i < $this->faker->numberBetween($minPerType, $minPerType + 3); $i++) {
                $note = Note::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $this->faker->paragraph,
                    'noteable_type' => get_class($host),
                    'noteable_id' => $host->id,
                    'pinned' => $this->faker->boolean(20),
                    'team_id' => $teamId,
                    'user_created_id' => $this->randomUserId(),
                ]);
                $this->recordActivity($host, $note);
            }

            // Calls – 5 to 8 per host.
            for ($i = 0; $i < $this->faker->numberBetween($minPerType, $minPerType + 3); $i++) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))
                    ->setTime($this->faker->numberBetween(9, 16), 0);
                $call = Call::create($this->activityAttributes($host, 'callable', [
                    'name' => 'Call - '.$this->faker->sentence(3),
                    'description' => $this->faker->sentence,
                    'start_at' => $start->format($this->dateTimeFormat),
                    'finish_at' => (clone $start)->addMinutes(30)->format($this->dateTimeFormat),
                ]));
                $this->recordActivity($host, $call);
            }

            // Meetings – 5 to 8 per host.
            for ($i = 0; $i < $this->faker->numberBetween($minPerType, $minPerType + 3); $i++) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))
                    ->setTime($this->faker->numberBetween(9, 16), 0);
                $meeting = Meeting::create($this->activityAttributes($host, 'meetingable', [
                    'name' => 'Meeting - '.$this->faker->sentence(3),
                    'description' => $this->faker->sentence,
                    'start_at' => $start->format($this->dateTimeFormat),
                    'finish_at' => (clone $start)->addHour()->format($this->dateTimeFormat),
                ]));
                $this->recordActivity($host, $meeting);
            }

            // Lunches – 5 to 8 per host.
            for ($i = 0; $i < $this->faker->numberBetween($minPerType, $minPerType + 3); $i++) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-30, 30))->setTime(12, 0);
                $lunch = Lunch::create($this->activityAttributes($host, 'lunchable', [
                    'name' => 'Lunch with '.($host->name ?? $host->first_name ?? 'contact'),
                    'description' => $this->faker->sentence,
                    'start_at' => $start->format($this->dateTimeFormat),
                    'finish_at' => (clone $start)->addHour()->format($this->dateTimeFormat),
                ]));
                $this->recordActivity($host, $lunch);
            }

            if ($bar) {
                $bar->advance();
            }
        }

        if ($bar) {
            $bar->finish();
            $this->writeln('');
        }
    }

    /**
     * Build the common attributes for a polymorphic activity record.
     */
    protected function activityAttributes($host, string $morphName, array $extra = []): array
    {
        return array_merge([
            'external_id' => Uuid::uuid4()->toString(),
            "{$morphName}_type" => get_class($host),
            "{$morphName}_id" => $host->id,
            'team_id' => $host->team_id ?? $this->randomTeamId(),
            'user_owner_id' => $this->randomUserId(),
            'user_assigned_id' => $this->randomUserId(),
            'user_created_id' => $this->randomUserId(),
        ], $extra);
    }

    /**
     * Record a CRM Activity (timeline entry) for a created task / note /
     * call / meeting / lunch so the host's activity feed shows it.
     *
     * Mirrors the structure that the Livewire components write when the
     * record is created via the UI:
     *   - causeable_*    : the user who performed the action
     *   - timelineable_* : the host model the activity belongs to
     *   - recordable_*   : the underlying activity record (call, note, …)
     */
    protected function recordActivity($host, $record, string $event = 'created'): void
    {
        $userId = $this->randomUserId();
        $userClass = config('auth.providers.users.model', \App\User::class);

        Activity::create([
            'external_id' => Uuid::uuid4()->toString(),
            'log_name' => 'default',
            'description' => $event,
            'event' => $event,
            'causeable_type' => $userId ? $userClass : null,
            'causeable_id' => $userId,
            'timelineable_type' => get_class($host),
            'timelineable_id' => $host->id,
            'recordable_type' => get_class($record),
            'recordable_id' => $record->id,
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
            Lead::class => Lead::inRandomOrder()->limit(150)->get(),
            Deal::class => Deal::inRandomOrder()->limit(150)->get(),
            Quote::class => Quote::inRandomOrder()->limit(100)->get(),
            Order::class => Order::inRandomOrder()->limit(100)->get(),
            Person::class => Person::inRandomOrder()->limit(200)->get(),
            Organisation::class => Organisation::inRandomOrder()->limit(200)->get(),
            Product::class => Product::inRandomOrder()->limit(100)->get(),
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
                        'external_id' => Uuid::uuid4()->toString(),
                        'field_id' => $field->id,
                        'field_valueable_type' => $modelClass,
                        'field_valueable_id' => $host->id,
                        'value' => $this->generateFieldValue($field),
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
}
