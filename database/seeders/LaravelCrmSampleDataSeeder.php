<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
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

        DB::transaction(function () {
            $this->seedUsers();
            $this->seedTeams();
            $this->seedProductCatalogue();
            $this->seedCustomFields();
            $this->seedOrganisationsAndPeople();
            $this->seedClients();
            $this->seedLeadsAndDeals();
            $this->seedSalesDocuments();
            $this->seedActivities();
            $this->applyCustomFieldValues();
        });
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

        for ($i = 0; $i < 20; $i++) {
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
        for ($i = 0; $i < 50; $i++) {
            Organisation::create([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $this->faker->company,
                'description' => $this->faker->catchPhrase,
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
            ]);
        }

        $organisationIds = Organisation::pluck('id');

        for ($i = 0; $i < 120; $i++) {
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
        }
    }

    /* ----------------------------------------------------------------- */
    /* Clients (customers)                                               */
    /* ----------------------------------------------------------------- */

    protected function seedClients(): void
    {
        $organisations = Organisation::inRandomOrder()->limit(20)->get();
        $people = Person::inRandomOrder()->limit(15)->get();

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
        }
    }

    /* ----------------------------------------------------------------- */
    /* Leads & Deals                                                     */
    /* ----------------------------------------------------------------- */

    protected function seedLeadsAndDeals(): void
    {
        $organisationIds = Organisation::pluck('id');
        $peopleIds = Person::pluck('id');

        for ($i = 0; $i < 40; $i++) {
            Lead::create([
                'external_id' => Uuid::uuid4()->toString(),
                'title' => $this->faker->catchPhrase,
                'description' => $this->faker->paragraph,
                'amount' => $this->faker->numberBetween(100, 100000) * 100,
                'currency' => 'USD',
                'person_id' => $peopleIds->isNotEmpty() ? $peopleIds->random() : null,
                'organisation_id' => $organisationIds->isNotEmpty() ? $organisationIds->random() : null,
                'expected_close' => Carbon::now()->addDays($this->faker->numberBetween(7, 90)),
                'team_id' => $this->randomTeamId(),
                'user_owner_id' => $this->randomUserId(),
                'user_assigned_id' => $this->randomUserId(),
                'user_created_id' => $this->randomUserId(),
                'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 60)),
            ]);
        }

        foreach (Lead::inRandomOrder()->limit(25)->get() as $lead) {
            Deal::create([
                'external_id' => Uuid::uuid4()->toString(),
                'lead_id' => $lead->id,
                'person_id' => $lead->person_id,
                'organisation_id' => $lead->organisation_id,
                'title' => $lead->title.' (Deal)',
                'description' => $this->faker->paragraph,
                'amount' => $lead->amount,
                'currency' => 'USD',
                'expected_close' => $lead->expected_close,
                'team_id' => $lead->team_id,
                'user_owner_id' => $lead->user_owner_id,
                'user_assigned_id' => $lead->user_assigned_id,
                'user_created_id' => $this->randomUserId(),
                'created_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 30)),
            ]);
        }
    }

    /* ----------------------------------------------------------------- */
    /* Quotes / Orders / Invoices / Deliveries / Purchase Orders         */
    /* ----------------------------------------------------------------- */

    protected function seedSalesDocuments(): void
    {
        $deals = Deal::all();
        $docNumber = 1000;

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
                'issue_at' => Carbon::now()->subDays($this->faker->numberBetween(5, 30)),
                'expire_at' => Carbon::now()->addDays($this->faker->numberBetween(15, 60)),
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
                'issue_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 14)),
                'due_date' => Carbon::now()->addDays($this->faker->numberBetween(7, 30)),
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
                    'issue_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 14)),
                    'delivery_date' => Carbon::now()->addDays($this->faker->numberBetween(7, 30)),
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
        }
    }

    /* ----------------------------------------------------------------- */
    /* Tasks / Notes / Calls / Meetings / Lunches                        */
    /* ----------------------------------------------------------------- */

    protected function seedActivities(): void
    {
        $hosts = collect()
            ->merge(Lead::inRandomOrder()->limit(20)->get())
            ->merge(Deal::inRandomOrder()->limit(20)->get())
            ->merge(Person::inRandomOrder()->limit(20)->get())
            ->merge(Organisation::inRandomOrder()->limit(20)->get());

        if ($hosts->isEmpty()) {
            return;
        }

        foreach ($hosts as $host) {
            for ($i = 0; $i < $this->faker->numberBetween(0, 2); $i++) {
                Task::create($this->activityAttributes($host, 'taskable', [
                    'name' => $this->faker->sentence(4),
                    'description' => $this->faker->paragraph,
                    'due_at' => Carbon::now()->addDays($this->faker->numberBetween(1, 30)),
                ]));
            }

            if ($this->faker->boolean(75)) {
                Note::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $this->faker->paragraph,
                    'noteable_type' => get_class($host),
                    'noteable_id' => $host->id,
                    'pinned' => $this->faker->boolean(20),
                    'team_id' => $host->team_id ?? $this->randomTeamId(),
                    'user_created_id' => $this->randomUserId(),
                ]);
            }

            if ($this->faker->boolean(50)) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-15, 15))
                    ->setTime($this->faker->numberBetween(9, 16), 0);
                Call::create($this->activityAttributes($host, 'callable', [
                    'name' => 'Call - '.$this->faker->sentence(3),
                    'description' => $this->faker->sentence,
                    'start_at' => $start,
                    'finish_at' => (clone $start)->addMinutes(30),
                ]));
            }

            if ($this->faker->boolean(40)) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-15, 15))
                    ->setTime($this->faker->numberBetween(9, 16), 0);
                Meeting::create($this->activityAttributes($host, 'meetingable', [
                    'name' => 'Meeting - '.$this->faker->sentence(3),
                    'description' => $this->faker->sentence,
                    'start_at' => $start,
                    'finish_at' => (clone $start)->addHour(),
                ]));
            }

            if ($this->faker->boolean(20)) {
                $start = Carbon::now()->addDays($this->faker->numberBetween(-15, 15))->setTime(12, 0);
                Lunch::create($this->activityAttributes($host, 'lunchable', [
                    'name' => 'Lunch with '.($host->name ?? $host->first_name ?? 'contact'),
                    'description' => $this->faker->sentence,
                    'start_at' => $start,
                    'finish_at' => (clone $start)->addHour(),
                ]));
            }
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
            Lead::class => Lead::inRandomOrder()->limit(15)->get(),
            Deal::class => Deal::inRandomOrder()->limit(15)->get(),
            Quote::class => Quote::inRandomOrder()->limit(10)->get(),
            Order::class => Order::inRandomOrder()->limit(10)->get(),
            Person::class => Person::inRandomOrder()->limit(20)->get(),
            Organisation::class => Organisation::inRandomOrder()->limit(20)->get(),
            Product::class => Product::inRandomOrder()->limit(10)->get(),
        ];

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
