<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\DealProduct;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Email;
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
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\Task;

class LaravelCrmSampleDataSeeder extends Seeder
{
    /**
     * The start date for sample data (3 years ago).
     */
    protected Carbon $startDate;

    /**
     * The end date for sample data (today).
     */
    protected Carbon $endDate;

    /**
     * The first CRM user ID (owner of all records).
     */
    protected int $userId;

    /**
     * Cached pipeline stages keyed by pipeline model class.
     */
    protected array $pipelineStages = [];

    /**
     * Cached pipeline IDs keyed by model class.
     */
    protected array $pipelineIds = [];

    /**
     * Currency code from settings.
     */
    protected string $currency = 'USD';

    /**
     * Seeded products collection.
     */
    protected $products;

    /**
     * Seeded organizations collection.
     */
    protected $organizations;

    /**
     * Seeded people collection.
     */
    protected $people;

    /**
     * Seeded leads collection.
     */
    protected $leads;

    /**
     * Seeded deals collection.
     */
    protected $deals;

    /**
     * Seeded quotes collection.
     */
    protected $quotes;

    /**
     * Seeded orders collection.
     */
    protected $orders;

    /**
     * Seeded invoices collection.
     */
    protected $invoices;

    /**
     * Run the database seeds.
     */
    public function run(bool $fresh = false): void
    {
        $this->startDate = now()->subYears(3)->startOfDay();
        $this->endDate = now();

        // Get the first user with CRM access, or the first user
        $userModel = app(config('auth.providers.users.model', 'App\Models\User'));
        $user = $userModel::first();
        if (! $user) {
            $this->command->error('No user found. Please create a user first.');

            return;
        }
        $this->userId = $user->id;

        // Get currency from settings
        $currencySetting = Setting::where('name', 'currency')->first();
        $this->currency = $currencySetting->value ?? 'USD';

        // Cache pipeline data
        $this->cachePipelineData();

        if ($fresh) {
            $this->truncateSampleData();
        }

        // Disable auditing to avoid thousands of audit records
        $this->disableAuditing();

        // Disable query log to prevent memory issues with large dataset
        DB::disableQueryLog();

        $this->seedLeadSources();
        $this->seedProductCategoriesAndProducts();
        $this->seedOrganizations();
        $this->seedPeople();
        $this->seedLeads();
        $this->seedDeals();
        $this->seedQuotes();
        $this->seedOrders();
        $this->seedInvoices();
        $this->seedDeliveries();
        $this->seedPurchaseOrders();
        $this->seedActivities();
        $this->seedLabels();
        $this->seedCustomFieldGroups();

        // Re-enable auditing
        $this->enableAuditing();
    }

    // =========================================================================
    // Pipeline & Utility Helpers
    // =========================================================================

    protected function cachePipelineData(): void
    {
        $pipelines = Pipeline::with('pipelineStages')->get();

        foreach ($pipelines as $pipeline) {
            $this->pipelineIds[$pipeline->model] = $pipeline->id;
            $this->pipelineStages[$pipeline->model] = $pipeline->pipelineStages->sortBy('id')->values();
        }
    }

    protected function getPipelineId(string $modelClass): ?int
    {
        return $this->pipelineIds[$modelClass] ?? null;
    }

    protected function getPipelineStages(string $modelClass): Collection
    {
        return $this->pipelineStages[$modelClass] ?? collect();
    }

    protected function getRandomStage(string $modelClass, array $stageNames = []): ?PipelineStage
    {
        $stages = $this->getPipelineStages($modelClass);

        if (! empty($stageNames)) {
            $stages = $stages->whereIn('name', $stageNames);
        }

        return $stages->isNotEmpty() ? $stages->random() : null;
    }

    /**
     * Generate a weighted random date within the 3-year window.
     * Later dates are more likely (simulating business growth).
     * Adds seasonal fluctuation (Q1/Q3 busier, Dec/Jan slower).
     */
    protected function weightedRandomDate(?Carbon $after = null, ?Carbon $before = null): Carbon
    {
        $start = $after ? $after->copy() : $this->startDate->copy();
        $end = $before ? $before->copy() : $this->endDate->copy();

        // Ensure valid range
        if ($start->gte($end)) {
            return $start->copy();
        }

        $totalDays = $start->diffInDays($end);
        if ($totalDays === 0) {
            return $start->copy();
        }

        // Generate date with growth bias (quadratic — more recent dates more likely)
        $random = mt_rand(0, 10000) / 10000; // 0.0 to 1.0
        $biased = pow($random, 0.7); // Bias towards later dates (lower exponent = more bias)
        $dayOffset = (int) round($biased * $totalDays);

        $date = $start->copy()->addDays($dayOffset);

        // Add seasonal fluctuation: busier in March/September, slower in December/January
        $month = $date->month;
        $seasonalWeights = [
            1 => 0.7, 2 => 0.85, 3 => 1.15, 4 => 1.1, 5 => 1.0, 6 => 0.95,
            7 => 0.85, 8 => 0.9, 9 => 1.15, 10 => 1.1, 11 => 1.0, 12 => 0.65,
        ];

        // Use seasonal weight as probability of keeping this date (vs regenerating)
        if (mt_rand(0, 100) / 100 > $seasonalWeights[$month]) {
            // Try once more with bias towards busier months
            return $this->weightedRandomDate($after, $before);
        }

        // Add random time of day (business hours bias: 8am-6pm)
        $hour = $this->randomBiasedInt(8, 18);
        $minute = mt_rand(0, 59);

        return $date->setTime($hour, $minute, mt_rand(0, 59));
    }

    /**
     * Generate random int with bell curve distribution around the midpoint.
     */
    protected function randomBiasedInt(int $min, int $max): int
    {
        $mid = ($min + $max) / 2;
        $std = ($max - $min) / 4;
        $value = $mid + $std * (mt_rand() / mt_getrandmax() + mt_rand() / mt_getrandmax() - 1);

        return max($min, min($max, (int) round($value)));
    }

    /**
     * Generate a random dollar amount within a range.
     */
    protected function randomAmount(float $min, float $max): float
    {
        return round($min + (mt_rand() / mt_getrandmax()) * ($max - $min), 2);
    }

    protected function disableAuditing(): void
    {
        // Disable audit globally if the config allows
        config(['audit.enabled' => false]);
    }

    protected function enableAuditing(): void
    {
        config(['audit.enabled' => true]);
    }

    /**
     * Backdate a model's timestamps without triggering events.
     */
    protected function backdateModel($model, Carbon $date): void
    {
        $table = $model->getTable();
        DB::table($table)->where('id', $model->id)->update([
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    protected function truncateSampleData(): void
    {
        $this->command->info('Truncating existing CRM entity data...');

        Schema::disableForeignKeyConstraints();

        $prefix = config('laravel-crm.db_table_prefix');

        $tables = [
            'activities', 'notes', 'tasks', 'calls', 'meetings', 'lunches', 'files',
            'delivery_products', 'deliveries',
            'purchase_order_lines', 'purchase_orders',
            'invoice_lines', 'invoices',
            'order_products', 'orders',
            'quote_products', 'quotes',
            'deal_products', 'deals',
            'leads',
            'contacts',
            'phones', 'emails', 'addresses',
            'people', 'organizations',
            'product_prices', 'products', 'product_categories',
            'lead_sources',
        ];

        // Also truncate the labelables pivot table
        $labelableTable = $prefix.'labelables';
        if (Schema::hasTable($labelableTable)) {
            DB::table($labelableTable)->truncate();
        }

        foreach ($tables as $table) {
            $fullTable = $prefix.$table;
            if (Schema::hasTable($fullTable)) {
                DB::table($fullTable)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    // =========================================================================
    // Lead Sources
    // =========================================================================

    protected function seedLeadSources(): void
    {
        $this->command->info('Seeding lead sources...');

        $sources = ['Website', 'Referral', 'Cold Call', 'Email Campaign', 'Social Media', 'Trade Show', 'Partner', 'Organic Search'];

        foreach ($sources as $source) {
            LeadSource::firstOrCreate(
                ['name' => $source],
                ['external_id' => Uuid::uuid4()->toString()]
            );
        }
    }

    // =========================================================================
    // Products
    // =========================================================================

    protected function seedProductCategoriesAndProducts(): void
    {
        $this->command->info('Seeding product categories and products...');

        $categories = [
            'Software Licenses' => [
                ['name' => 'CRM Enterprise License', 'price' => 4999.00, 'cost' => 500.00],
                ['name' => 'CRM Professional License', 'price' => 2499.00, 'cost' => 250.00],
                ['name' => 'CRM Starter License', 'price' => 999.00, 'cost' => 100.00],
                ['name' => 'API Access Add-on', 'price' => 499.00, 'cost' => 50.00],
            ],
            'Consulting Services' => [
                ['name' => 'Implementation Consulting (per day)', 'price' => 1500.00, 'cost' => 600.00],
                ['name' => 'Data Migration Service', 'price' => 3500.00, 'cost' => 1400.00],
                ['name' => 'Custom Integration Development', 'price' => 5000.00, 'cost' => 2000.00],
                ['name' => 'Business Process Review', 'price' => 2000.00, 'cost' => 800.00],
            ],
            'Support Plans' => [
                ['name' => 'Premium Support (Annual)', 'price' => 2999.00, 'cost' => 300.00],
                ['name' => 'Standard Support (Annual)', 'price' => 1499.00, 'cost' => 150.00],
                ['name' => 'Emergency Support (per incident)', 'price' => 500.00, 'cost' => 200.00],
            ],
            'Training' => [
                ['name' => 'On-site Training (per day)', 'price' => 2500.00, 'cost' => 1000.00],
                ['name' => 'Remote Training Session (half day)', 'price' => 750.00, 'cost' => 300.00],
                ['name' => 'Self-paced Online Course', 'price' => 299.00, 'cost' => 30.00],
                ['name' => 'Admin Certification Program', 'price' => 1200.00, 'cost' => 480.00],
            ],
            'Hardware & Infrastructure' => [
                ['name' => 'Dedicated Server Setup', 'price' => 8000.00, 'cost' => 3200.00],
                ['name' => 'Cloud Hosting (Annual)', 'price' => 3600.00, 'cost' => 1440.00],
                ['name' => 'SSL Certificate (Annual)', 'price' => 199.00, 'cost' => 50.00],
                ['name' => 'Backup & Recovery Service', 'price' => 1200.00, 'cost' => 480.00],
            ],
        ];

        foreach ($categories as $categoryName => $products) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                ['external_id' => Uuid::uuid4()->toString()]
            );

            foreach ($products as $productData) {
                $product = Product::create([
                    'name' => $productData['name'],
                    'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productData['name']), 0, 3)).'-'.mt_rand(100, 999),
                    'description' => 'High-quality '.$productData['name'].' for business operations.',
                    'product_category_id' => $category->id,
                    'active' => true,
                    'user_created_id' => $this->userId,
                    'user_owner_id' => $this->userId,
                ]);

                ProductPrice::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'product_id' => $product->id,
                    'unit_price' => $productData['price'],
                    'cost_per_unit' => $productData['cost'],
                    'direct_cost' => $productData['cost'] * 0.8,
                    'currency' => $this->currency,
                ]);
            }
        }

        $this->products = Product::with('productPrices')->get();
        $this->command->info("  → Created {$this->products->count()} products in ".count($categories).' categories');
    }

    // =========================================================================
    // Organizations
    // =========================================================================

    protected function seedOrganizations(): void
    {
        $this->command->info('Seeding organizations...');

        $orgNames = [
            'Acme Corporation', 'TechVista Solutions', 'Global Dynamics', 'Pinnacle Systems',
            'Atlas Manufacturing', 'Quantum Innovations', 'Meridian Partners', 'Apex Digital',
            'Sterling & Associates', 'BlueShift Technologies', 'Cascade Networks', 'Horizon Health',
            'Iron Bridge Consulting', 'Nexus Financial', 'Oakwood Properties', 'Prism Analytics',
            'Red Rock Mining', 'Summit Logistics', 'Timber Creek Foods', 'Vanguard Security',
            'Alpine Data Systems', 'Beacon Software', 'Coral Bay Trading', 'Delta Force IT',
            'Eclipse Renewable', 'Falcon Aerospace', 'Greenfield Agriculture', 'Harbor Tech',
            'Infinity Labs', 'Jupiter Telecom', 'Kestrel Media', 'Lighthouse Financial',
            'Maple Leaf Services', 'Nordic Designs', 'Onyx Industries', 'Pacific Rim Trading',
            'Quest Biotech', 'Rover Automotive', 'Sapphire Electronics', 'Trident Marine',
            'Unity Software', 'Vertex Engineering', 'Windmill Energy', 'Xenon Pharmaceuticals',
            'Yellowstone Capital', 'Zenith Construction', 'Archway Legal', 'Bridgeport Manufacturing',
            'Copper Mountain Mining', 'Daybreak Education', 'Emerald City Design', 'Frontier Gas & Oil',
            'Glacier Peak Outdoors', 'Highland Retail Group', 'Island Creek Fisheries', 'Jasper Stone Works',
            'Keystone Investments', 'Liberty Shipping', 'Monarch Insurance', 'Northern Star Logistics',
            'Olympus Medical', 'Phoenix Digital Marketing', 'Rainier Cloud Services', 'Silverline Transport',
            'Thunderbolt Electric', 'Upland Timber Co', 'Voyager Space Tech', 'Westfield Real Estate',
            'Apex Performance', 'BrightPath Consulting', 'ClearView Analytics', 'DawnTech AI',
            'EverGreen Solutions', 'FlexiWork Systems', 'GoldRush Ventures', 'HighTide Marine',
            'InnoCore Labs', 'JetStream Networks', 'KnightBridge Advisors', 'LunarSoft Inc',
            'MavenTech Group', 'NorthStar Guidance', 'OceanBlue Holdings', 'PrimeFocus Media',
            'QuickSilver Payments', 'RedPine Analytics', 'SkyBridge Capital', 'TrueNorth Data',
            'UltraViolet Creations', 'Velocity Partners', 'WaveRunner Tech', 'XcelPro Services',
            'YieldMax Agriculture', 'ZenithPoint Solutions', 'ArcLight Power', 'BoldStrike Security',
            'CoralTech Innovations', 'DiamondEdge Tools',
            // --- Batch 2: Additional organizations ---
            'Amber Wave Energy', 'BlueCrest Marine', 'Canyon Ridge Homes', 'DataStream AI',
            'EaglePeak Mining', 'FrostByte Computing', 'GraniteShield Security', 'HavenBridge Health',
            'IronClad Defense', 'JadeGarden Wellness', 'KaleidoScope Design', 'LavaFlow Studios',
            'MistralWind Power', 'NovaFlare Robotics', 'ObsidianCore Labs', 'PeakView Analytics',
            'QuartzLine Optics', 'RiverStone Capital', 'SilverOak Consulting', 'TerraForge Mining',
            'UltraBeam Lasers', 'VerdantFields Agri', 'WhiteHawk Aviation', 'XenonPulse Tech',
            'YewTree Partners', 'ZephyrCloud SaaS', 'AeroVista Drones', 'BramblePath Logistics',
            'CedarPoint Financial', 'DuskTill Software', 'ElmGrove Properties', 'FireBrand Media',
            'GoldenRatio Design', 'HarborLight Shipping', 'IceCap Refrigeration', 'JuniperNet Telecom',
            'KiteString Ventures', 'LimeSpark Digital', 'MoonGlow Cosmetics', 'NimbleStack Dev',
            'OrionBelt Aerospace', 'PineCone Analytics', 'QuantumLeap AI', 'RedCedar Timber',
            'SnowPeak Outdoors', 'ThunderRock Mining', 'UrbanNest Realty', 'VoltEdge Power',
            'WillowBrook Foods', 'XtremeForce Sports', 'Yellowfin Trading', 'ZenithWave Telecom',
            'AlphaForge Industries', 'BluePrint Architects', 'CircuitBend Electronics', 'DawnBreak Solar',
            'EchoValley Sound', 'FalconRise Logistics', 'GlacierBay Seafood', 'HemlockWood Products',
            'IvoryTower Education', 'JetPulse Aviation', 'KingsGate Holdings', 'LanternLight Events',
            'MapleCrest Bakeries', 'NorthShore Insurance', 'OpalStone Jewelry', 'PrairieWind Farms',
            'QuickRoute Delivery', 'RavenClaw Security', 'SunRise Healthcare', 'TidePool Marine Bio',
            'UpperDeck Sports', 'VineHill Wineries', 'WestGate Retail', 'XrossRoads Consulting',
            'YachtLine Marine', 'ZincoAlloy Metals', 'AnchorBay Logistics', 'BirchField Paper',
            'CloudNine Travel', 'DeepRoot Forestry', 'EliteEdge Training', 'FoxHollow Veterinary',
            'GreenMile Transport', 'HillCrest Dental', 'IndigoSky Airlines', 'JoltWire Electric',
            'KeenSight Optics', 'LionHeart Legal', 'MarbleCut Masonry', 'NetWoven Textiles',
            'OverDrive Automotive', 'PearlDive Aquatics', 'QuestMark Surveying', 'RidgeLine Roofing',
            'SteelArch Construction', 'TrueScale Engineering', 'UnityBridge Foundation', 'ValorShield Insurance',
            'WinterPine Retreats', 'XcelRate Fulfillment', 'YieldPoint Agriculture', 'ZenPath Meditation',
            'AspenTrail Outdoors', 'BoltForce Hardware', 'CoastalBreeze Hotels', 'DragonScale Gaming',
            'EmberGlow Candles', 'FlintRock Geology', 'GoldLeaf Publishing', 'HarvestMoon Organics',
            'IronBridge Fabrication', 'JasperCreek Ranch', 'KeyVault Cybersecurity', 'LarkSong Music',
            'MetalCraft Welding', 'NexGen Genomics', 'OceanCrest Cruises', 'PilotWave Simulators',
            'QuillPen Content', 'RusticOak Furniture', 'SkyLark Drones', 'TechPulse Innovations',
            'UpliftCare Senior Living', 'ViperStrike Defense', 'WaveLength Audio', 'XactFit Prosthetics',
            'YonderStar Astronomy', 'ZestFuel Nutrition', 'ArcticFox Expeditions', 'BlazeTrail Marketing',
            'CopperKettle Brewing', 'DeltaRun Couriers', 'EverBloom Floristry', 'FrostPeak Ski Resorts',
            'GreenThumb Landscapes', 'HighTower Scaffolding', 'IronSide Fencing', 'JetBlack Detailing',
            'KnightWatch Security', 'LeafSpring Automotive', 'MidStream Resources', 'NorthPole Refrigeration',
            'OakHammer Carpentry', 'PlatinumPath Wealth', 'QuickFix Plumbing', 'RedHawk Drones',
            'SilverStream Water', 'TopNotch Recruitment', 'UltraFit Gyms', 'VanguardPoint Strategy',
            'WhiteRiver Kayaks', 'XpressShip Logistics', 'YellowBrick Learning', 'ZeroGrav VR',
            'ApexPeak Adventures', 'BlackDiamond Ski Co', 'CrossWind Sailing', 'DarkMatter Physics',
            'EagleNest Ventures', 'FireFly Biotech', 'GoldStrike Exploration', 'HydroFlow Plumbing',
            'IceStorm Cooling', 'JunglePath Eco Tours', 'KaleFarm Organics', 'LightHouse Beacons',
            'MossCreek Nursery', 'NightOwl Software', 'OpenSky Paragliding', 'PineValley Golf',
            'QuarryStone Aggregates', 'RocketLaunch Events', 'SandDune Resorts', 'ThunderBay Fishing',
            'UniCore Processors', 'VelvetRope Events', 'WindChime Arts', 'XtraTerra Space Mining',
        ];

        $cities = [
            ['city' => 'New York', 'state' => 'NY', 'country' => 'United States', 'code' => '10001'],
            ['city' => 'Los Angeles', 'state' => 'CA', 'country' => 'United States', 'code' => '90001'],
            ['city' => 'Chicago', 'state' => 'IL', 'country' => 'United States', 'code' => '60601'],
            ['city' => 'Houston', 'state' => 'TX', 'country' => 'United States', 'code' => '77001'],
            ['city' => 'Phoenix', 'state' => 'AZ', 'country' => 'United States', 'code' => '85001'],
            ['city' => 'San Francisco', 'state' => 'CA', 'country' => 'United States', 'code' => '94102'],
            ['city' => 'Seattle', 'state' => 'WA', 'country' => 'United States', 'code' => '98101'],
            ['city' => 'Denver', 'state' => 'CO', 'country' => 'United States', 'code' => '80201'],
            ['city' => 'Boston', 'state' => 'MA', 'country' => 'United States', 'code' => '02101'],
            ['city' => 'Austin', 'state' => 'TX', 'country' => 'United States', 'code' => '73301'],
            ['city' => 'Miami', 'state' => 'FL', 'country' => 'United States', 'code' => '33101'],
            ['city' => 'Portland', 'state' => 'OR', 'country' => 'United States', 'code' => '97201'],
            ['city' => 'Atlanta', 'state' => 'GA', 'country' => 'United States', 'code' => '30301'],
            ['city' => 'Nashville', 'state' => 'TN', 'country' => 'United States', 'code' => '37201'],
            ['city' => 'Minneapolis', 'state' => 'MN', 'country' => 'United States', 'code' => '55401'],
            ['city' => 'London', 'state' => '', 'country' => 'United Kingdom', 'code' => 'EC1A 1BB'],
            ['city' => 'Sydney', 'state' => 'NSW', 'country' => 'Australia', 'code' => '2000'],
            ['city' => 'Toronto', 'state' => 'ON', 'country' => 'Canada', 'code' => 'M5H 2N2'],
        ];

        $streetNames = [
            'Main St', 'Oak Ave', 'Elm St', 'Park Blvd', 'Commerce Dr',
            'Industrial Way', 'Technology Pkwy', 'Innovation Dr', 'Market St', 'First Ave',
            'Broadway', 'Highland Rd', 'Sunset Blvd', 'Lake St', 'River Rd',
        ];

        $domains = [
            'com', 'co', 'io', 'tech', 'biz', 'net', 'org',
        ];

        foreach ($orgNames as $name) {
            $date = $this->weightedRandomDate();
            $city = $cities[array_rand($cities)];

            $org = Organization::create([
                'name' => $name,
                'description' => $name.' is a leading company in its industry.',
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
            ]);

            $this->backdateModel($org, $date);

            // Email
            $slug = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
            $domain = $domains[array_rand($domains)];
            Email::create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => 'info@'.$slug.'.'.$domain,
                'type' => 'work',
                'primary' => true,
                'emailable_type' => Organization::class,
                'emailable_id' => $org->id,
            ]);

            // Phone
            Phone::create([
                'external_id' => Uuid::uuid4()->toString(),
                'number' => '+1'.mt_rand(200, 999).mt_rand(100, 999).mt_rand(1000, 9999),
                'type' => 'work',
                'primary' => true,
                'phoneable_type' => Organization::class,
                'phoneable_id' => $org->id,
            ]);

            // Address
            Address::create([
                'external_id' => Uuid::uuid4()->toString(),
                'line1' => mt_rand(100, 9999).' '.$streetNames[array_rand($streetNames)],
                'line2' => mt_rand(0, 3) === 0 ? 'Suite '.mt_rand(100, 999) : null,
                'city' => $city['city'],
                'state' => $city['state'],
                'code' => $city['code'],
                'country' => $city['country'],
                'primary' => true,
                'addressable_type' => Organization::class,
                'addressable_id' => $org->id,
            ]);
        }

        $this->organizations = Organization::all();
        $this->command->info("  → Created {$this->organizations->count()} organizations");
    }

    // =========================================================================
    // People
    // =========================================================================

    protected function seedPeople(): void
    {
        $this->command->info('Seeding people...');

        $firstNames = [
            'James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda',
            'David', 'Elizabeth', 'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
            'Thomas', 'Sarah', 'Christopher', 'Karen', 'Charles', 'Lisa', 'Daniel', 'Nancy',
            'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra', 'Donald', 'Ashley',
            'Steven', 'Kimberly', 'Andrew', 'Emily', 'Paul', 'Donna', 'Joshua', 'Michelle',
            'Kenneth', 'Carol', 'Kevin', 'Amanda', 'Brian', 'Dorothy', 'George', 'Melissa',
            'Timothy', 'Deborah', 'Ronald', 'Stephanie', 'Edward', 'Rebecca', 'Jason', 'Sharon',
            'Jeffrey', 'Laura', 'Ryan', 'Cynthia', 'Jacob', 'Kathleen', 'Gary', 'Amy',
            'Nicholas', 'Angela', 'Eric', 'Shirley', 'Jonathan', 'Anna', 'Stephen', 'Brenda',
            'Larry', 'Pamela', 'Justin', 'Emma', 'Scott', 'Nicole', 'Brandon', 'Helen',
            'Benjamin', 'Samantha', 'Samuel', 'Katherine', 'Raymond', 'Christine', 'Gregory', 'Debra',
            'Frank', 'Rachel', 'Alexander', 'Carolyn', 'Patrick', 'Janet', 'Jack', 'Catherine',
        ];

        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
            'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas',
            'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White',
            'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young',
            'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
            'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell',
            'Carter', 'Roberts', 'Phillips', 'Evans', 'Turner', 'Parker', 'Collins', 'Edwards',
            'Stewart', 'Morris', 'Murphy', 'Cook', 'Rogers', 'Morgan', 'Peterson', 'Cooper',
        ];

        $titles = ['Mr', 'Mrs', 'Ms', 'Dr', null, null, null]; // Most have no title

        $jobTitles = [
            'CEO', 'CTO', 'CFO', 'COO', 'VP of Sales', 'VP of Engineering',
            'Director of Operations', 'Marketing Director', 'IT Manager',
            'Sales Manager', 'Account Manager', 'Project Manager',
            'Software Engineer', 'Business Analyst', 'Product Manager',
            'Head of Procurement', 'Purchasing Manager', 'Finance Director',
            'Operations Manager', 'Technical Lead',
        ];

        $count = 800;
        for ($i = 0; $i < $count; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $org = $this->organizations->random();
            $date = $this->weightedRandomDate();

            $person = Person::create([
                'title' => $titles[array_rand($titles)],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'organization_id' => $org->id,
                'description' => $jobTitles[array_rand($jobTitles)].' at '.$org->name,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
            ]);

            $this->backdateModel($person, $date);

            // Email
            $orgSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $org->name));
            Email::create([
                'external_id' => Uuid::uuid4()->toString(),
                'address' => strtolower($firstName).'.'.strtolower($lastName).'@'.$orgSlug.'.com',
                'type' => 'work',
                'primary' => true,
                'emailable_type' => Person::class,
                'emailable_id' => $person->id,
            ]);

            // Phone (70% have a phone)
            if (mt_rand(1, 100) <= 70) {
                Phone::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'number' => '+1'.mt_rand(200, 999).mt_rand(100, 999).mt_rand(1000, 9999),
                    'type' => 'work',
                    'primary' => true,
                    'phoneable_type' => Person::class,
                    'phoneable_id' => $person->id,
                ]);
            }
        }

        $this->people = Person::all();
        $this->command->info("  → Created {$this->people->count()} people");
    }

    // =========================================================================
    // Leads
    // =========================================================================

    protected function seedLeads(): void
    {
        $this->command->info('Seeding leads...');

        $leadTitles = [
            'Interested in %s', 'Enquiry about %s', '%s evaluation', 'Request for %s proposal',
            '%s for Q%d', 'Potential %s deal', '%s upgrade request', 'New %s opportunity',
            '%s renewal discussion', 'Follow up: %s', '%s requirements review',
        ];

        $productTerms = [
            'Enterprise CRM', 'Cloud Hosting', 'Consulting Services', 'Data Migration',
            'Support Plan', 'Training Program', 'Custom Development', 'API Integration',
            'Analytics Suite', 'Security Audit', 'Infrastructure Review', 'Platform Upgrade',
        ];

        $leadSources = LeadSource::all();
        $pipelineId = $this->getPipelineId(Lead::class);
        $stages = $this->getPipelineStages(Lead::class);

        // Lead stage IDs:
        // 1=New, 2=Appointment Scheduled, 3=Qualified To Buy, 4=Presentation Scheduled,
        // 5=Decision Maker Bought-In, 6=Contract Sent, 7=Closed Won, 8=Closed Lost

        $count = 4000;
        $converted = [];

        for ($i = 0; $i < $count; $i++) {
            $date = $this->weightedRandomDate();
            $person = $this->people->random();
            $org = $person->organization;

            $titleTemplate = $leadTitles[array_rand($leadTitles)];
            $productTerm = $productTerms[array_rand($productTerms)];
            $quarter = ceil($date->month / 3);
            $title = sprintf($titleTemplate, $productTerm, $quarter);

            $amount = $this->randomAmount(500, 75000);

            // Determine stage based on age and randomness
            $daysSinceCreation = $date->diffInDays(now());
            $isConverted = false;
            $stage = $stages->first(); // Default: New

            if ($daysSinceCreation > 90) {
                // Old leads: most are resolved
                $rand = mt_rand(1, 100);
                if ($rand <= 40) {
                    // Converted (Closed Won)
                    $stage = $stages->firstWhere('name', 'Closed Won') ?? $stages->last();
                    $isConverted = true;
                } elseif ($rand <= 65) {
                    // Lost
                    $stage = $stages->firstWhere('name', 'Closed Lost') ?? $stages->last();
                } else {
                    // Still in progress
                    $stage = $stages->whereNotIn('name', ['Closed Won', 'Closed Lost'])->random();
                }
            } elseif ($daysSinceCreation > 30) {
                // Mid-age leads: some progressed
                $rand = mt_rand(1, 100);
                if ($rand <= 25) {
                    $stage = $stages->firstWhere('name', 'Closed Won') ?? $stages->last();
                    $isConverted = true;
                } elseif ($rand <= 40) {
                    $stage = $stages->firstWhere('name', 'Closed Lost') ?? $stages->last();
                } else {
                    $stage = $stages->whereNotIn('name', ['Closed Won', 'Closed Lost'])->random();
                }
            } else {
                // Recent leads: mostly early stages
                $earlyStages = $stages->take(4);
                $stage = $earlyStages->random();
            }

            $lead = Lead::create([
                'title' => $title,
                'description' => 'Lead from '.$person->first_name.' '.$person->last_name.' at '.($org->name ?? 'Unknown').'. '.$title,
                'amount' => $amount,
                'currency' => $this->currency,
                'person_id' => $person->id,
                'organization_id' => $org->id ?? null,
                'lead_source_id' => $leadSources->isNotEmpty() ? $leadSources->random()->id : null,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'converted_at' => $isConverted ? $date->copy()->addDays(mt_rand(7, 60))->min(now()) : null,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            $this->backdateModel($lead, $date);

            if ($isConverted) {
                $converted[] = $lead;
            }
        }

        $this->leads = Lead::all();
        $this->command->info("  → Created {$this->leads->count()} leads (".count($converted).' converted)');
    }

    // =========================================================================
    // Deals
    // =========================================================================

    protected function seedDeals(): void
    {
        $this->command->info('Seeding deals...');

        $pipelineId = $this->getPipelineId(Deal::class);
        $stages = $this->getPipelineStages(Deal::class);

        // Deal stage IDs: 9=Draft, 10=Pending, 11=Closed Won, 12=Closed Lost

        // Create deals from converted leads
        $convertedLeads = Lead::whereNotNull('converted_at')->get();
        $dealCount = 0;

        foreach ($convertedLeads as $lead) {
            $date = $lead->converted_at ?? Carbon::parse($lead->created_at)->addDays(mt_rand(7, 30));
            $amount = ($lead->amount ?? mt_rand(50000, 7500000)) / 100; // Convert from stored cents back to dollars

            $daysSince = $date->diffInDays(now());
            $closedStatus = null;
            $closedAt = null;

            if ($daysSince > 30) {
                // Older deals: even three-way split — won / lost / open
                $rand = mt_rand(1, 100);
                if ($rand <= 30) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(10, 45));
                } elseif ($rand <= 64) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(7, 35));
                } else {
                    // Still open — even split between Draft and Pending
                    $stage = mt_rand(1, 100) <= 50
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            } elseif ($daysSince > 14) {
                // Mid-age deals: mix of resolved and open
                $rand = mt_rand(1, 100);
                if ($rand <= 18) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(5, 20));
                } elseif ($rand <= 40) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(5, 18));
                } else {
                    $stage = mt_rand(1, 100) <= 45
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            } else {
                // Recent deals: mostly open, few resolved
                $rand = mt_rand(1, 100);
                if ($rand <= 5) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(2, 10));
                } elseif ($rand <= 12) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(2, 10));
                } else {
                    $stage = mt_rand(1, 100) <= 35
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            }

            if ($closedAt && $closedAt->gt(now())) {
                $closedAt = now();
            }

            $deal = Deal::create([
                'title' => $lead->title,
                'description' => 'Deal originated from lead: '.$lead->title,
                'amount' => $amount,
                'currency' => $this->currency,
                'person_id' => $lead->person_id,
                'organization_id' => $lead->organization_id,
                'lead_id' => $lead->id,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'expected_close' => $date->copy()->addDays(mt_rand(30, 120)),
                'closed_status' => $closedStatus,
                'closed_at' => $closedAt,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create DealProduct line items
            $lineItems = $this->generateLineItems(mt_rand(1, 4));
            $dealSubtotal = 0;
            foreach ($lineItems as $item) {
                DealProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'deal_id' => $deal->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'currency' => $this->currency,
                    'tax_rate' => 10,
                ]);
                $dealSubtotal += $item['amount'];
            }

            // Update deal amount to match line items total
            DB::table($deal->getTable())->where('id', $deal->id)->update([
                'amount' => $dealSubtotal * 100,
            ]);

            $this->backdateModel($deal, $date);
            $dealCount++;
        }

        // Create some standalone deals (not from leads)
        $standaloneDealCount = 600;
        for ($i = 0; $i < $standaloneDealCount; $i++) {
            $date = $this->weightedRandomDate();
            $person = $this->people->random();
            $org = $person->organization;
            $amount = $this->randomAmount(1000, 100000);

            $daysSince = $date->diffInDays(now());
            $closedStatus = null;
            $closedAt = null;
            $stage = $stages->first();

            if ($daysSince > 30) {
                // Older standalone deals: even three-way split
                $rand = mt_rand(1, 100);
                if ($rand <= 30) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(10, 50));
                } elseif ($rand <= 64) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(7, 40));
                } else {
                    // Open — even split across Draft and Pending
                    $stage = mt_rand(1, 100) <= 50
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            } elseif ($daysSince > 14) {
                // Mid-age: some resolved, many still open
                $rand = mt_rand(1, 100);
                if ($rand <= 15) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(5, 18));
                } elseif ($rand <= 33) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(5, 15));
                } else {
                    $stage = mt_rand(1, 100) <= 45
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            } else {
                // Recent deals: mostly open, a few resolved quickly
                $rand = mt_rand(1, 100);
                if ($rand <= 5) {
                    $closedStatus = 'won';
                    $stage = $stages->firstWhere('name', 'Closed Won');
                    $closedAt = $date->copy()->addDays(mt_rand(1, 8));
                } elseif ($rand <= 12) {
                    $closedStatus = 'lost';
                    $stage = $stages->firstWhere('name', 'Closed Lost');
                    $closedAt = $date->copy()->addDays(mt_rand(1, 8));
                } else {
                    $stage = mt_rand(1, 100) <= 30
                        ? $stages->firstWhere('name', 'Pending')
                        : $stages->firstWhere('name', 'Draft');
                }
            }

            if ($closedAt && $closedAt->gt(now())) {
                $closedAt = now();
            }

            $deal = Deal::create([
                'title' => $person->first_name.' '.$person->last_name.' - '.($org->name ?? 'Direct').' deal',
                'description' => 'Business opportunity with '.($org->name ?? $person->first_name.' '.$person->last_name),
                'amount' => $amount,
                'currency' => $this->currency,
                'person_id' => $person->id,
                'organization_id' => $org->id ?? null,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'expected_close' => $date->copy()->addDays(mt_rand(30, 180)),
                'closed_status' => $closedStatus,
                'closed_at' => $closedAt,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create DealProduct line items
            $lineItems = $this->generateLineItems(mt_rand(1, 3));
            $dealSubtotal = 0;
            foreach ($lineItems as $item) {
                DealProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'deal_id' => $deal->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'currency' => $this->currency,
                    'tax_rate' => 10,
                ]);
                $dealSubtotal += $item['amount'];
            }

            // Update deal amount to match line items total
            DB::table($deal->getTable())->where('id', $deal->id)->update([
                'amount' => $dealSubtotal * 100,
            ]);

            $this->backdateModel($deal, $date);
        }

        $this->deals = Deal::all();
        $totalWon = Deal::where('closed_status', 'won')->count();
        $totalLost = Deal::where('closed_status', 'lost')->count();
        $totalOpen = Deal::whereNull('closed_status')->count();
        $this->command->info("  → Created {$this->deals->count()} deals ({$totalWon} won, {$totalLost} lost, {$totalOpen} open)");
    }

    // =========================================================================
    // Quotes
    // =========================================================================

    protected function seedQuotes(): void
    {
        $this->command->info('Seeding quotes...');

        $pipelineId = $this->getPipelineId(Quote::class);
        $stages = $this->getPipelineStages(Quote::class);

        // Quote stages: 13=Draft, 14=Sent, 15=Accepted, 16=Rejected, 17=Ordered

        $wonDeals = Deal::where('closed_status', 'won')->get();
        $quoteCount = 0;

        foreach ($wonDeals as $deal) {
            // 80% of won deals get a quote
            if (mt_rand(1, 100) > 80) {
                continue;
            }

            $dealCreatedAt = Carbon::parse($deal->created_at);
            $date = $dealCreatedAt->copy()->addDays(mt_rand(3, 21));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 7));
            }

            $daysSince = $date->diffInDays(now());

            // Determine stage
            $acceptedAt = null;
            $rejectedAt = null;
            $stage = $stages->first(); // Draft

            if ($daysSince > 30) {
                $rand = mt_rand(1, 100);
                if ($rand <= 70) {
                    $stage = $stages->firstWhere('name', 'Accepted');
                    $acceptedAt = $date->copy()->addDays(mt_rand(5, 30));
                } elseif ($rand <= 85) {
                    $stage = $stages->firstWhere('name', 'Ordered');
                    $acceptedAt = $date->copy()->addDays(mt_rand(5, 20));
                } else {
                    $stage = $stages->firstWhere('name', 'Rejected');
                    $rejectedAt = $date->copy()->addDays(mt_rand(7, 30));
                }
            } elseif ($daysSince > 7) {
                $stage = $stages->firstWhere('name', 'Sent');
            }

            if ($acceptedAt && $acceptedAt->gt(now())) {
                $acceptedAt = now();
            }
            if ($rejectedAt && $rejectedAt->gt(now())) {
                $rejectedAt = now();
            }

            // Build line items
            $lineItems = $this->generateLineItems(mt_rand(1, 4));
            $subtotal = array_sum(array_column($lineItems, 'amount'));
            $taxRate = 10; // 10% tax
            $tax = round($subtotal * $taxRate / 100, 2);
            $total = $subtotal + $tax;

            $quote = Quote::create([
                'title' => 'Quote for '.($deal->organization->name ?? $deal->person->first_name ?? 'Client'),
                'description' => 'Quote related to deal: '.$deal->title,
                'reference' => 'REF-'.strtoupper(substr(md5($deal->id.time().$quoteCount), 0, 8)),
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'person_id' => $deal->person_id,
                'organization_id' => $deal->organization_id,
                'currency' => $this->currency,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'discount' => 0,
                'adjustments' => 0,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'issue_at' => $date,
                'expire_at' => $date->copy()->addDays(30),
                'accepted_at' => $acceptedAt,
                'rejected_at' => $rejectedAt,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create QuoteProduct records
            foreach ($lineItems as $item) {
                QuoteProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'currency' => $this->currency,
                    'tax_rate' => $taxRate,
                ]);
            }

            $this->backdateModel($quote, $date);
            $quoteCount++;
        }

        // Add quotes from open/pending deals
        $pendingDeals = Deal::whereNull('closed_status')->take(200)->get();
        foreach ($pendingDeals as $deal) {
            $dealCreatedAt = Carbon::parse($deal->created_at);
            $date = $dealCreatedAt->copy()->addDays(mt_rand(1, 14));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 3));
            }

            $stage = $stages->whereIn('name', ['Draft', 'Sent'])->random();

            $lineItems = $this->generateLineItems(mt_rand(1, 3));
            $subtotal = array_sum(array_column($lineItems, 'amount'));
            $taxRate = 10;
            $tax = round($subtotal * $taxRate / 100, 2);
            $total = $subtotal + $tax;

            $quote = Quote::create([
                'title' => 'Quote for '.($deal->organization->name ?? $deal->person->first_name ?? 'Client'),
                'description' => 'Quote for '.($deal->title ?? 'pending deal'),
                'reference' => 'REF-'.strtoupper(substr(md5($deal->id.mt_rand()), 0, 8)),
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'person_id' => $deal->person_id,
                'organization_id' => $deal->organization_id,
                'currency' => $this->currency,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'discount' => 0,
                'adjustments' => 0,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'issue_at' => $date,
                'expire_at' => $date->copy()->addDays(30),
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            foreach ($lineItems as $item) {
                QuoteProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'currency' => $this->currency,
                    'tax_rate' => $taxRate,
                ]);
            }

            $this->backdateModel($quote, $date);
        }

        // Add standalone quotes (not linked to deals) — fills out to ~55/month
        $standaloneQuoteTitles = [
            'Quotation for %s', 'Service proposal for %s', '%s — pricing estimate',
            'Price quote for %s', '%s — project quotation', 'Estimate for %s services',
            'Proposal for %s', '%s — cost breakdown', 'Budget proposal for %s',
            'Solution pricing for %s', '%s — service agreement quote', 'Custom quote for %s',
        ];

        $currentQuoteCount = Quote::count();
        $targetTotal = 2000; // ~55/month over 36 months
        $standaloneNeeded = max(0, $targetTotal - $currentQuoteCount);
        $standaloneCreated = 0;

        for ($i = 0; $i < $standaloneNeeded; $i++) {
            $date = $this->weightedRandomDate();
            $person = $this->people->random();
            $org = $person->organization;
            $orgName = $org->name ?? 'Client';

            $titleTemplate = $standaloneQuoteTitles[array_rand($standaloneQuoteTitles)];
            $title = sprintf($titleTemplate, $orgName);

            $daysSince = $date->diffInDays(now());

            // Determine stage based on age
            $acceptedAt = null;
            $rejectedAt = null;
            $stage = $stages->first(); // Draft

            if ($daysSince > 45) {
                $rand = mt_rand(1, 100);
                if ($rand <= 55) {
                    $stage = $stages->firstWhere('name', 'Accepted');
                    $acceptedAt = $date->copy()->addDays(mt_rand(5, 30));
                } elseif ($rand <= 75) {
                    $stage = $stages->firstWhere('name', 'Ordered');
                    $acceptedAt = $date->copy()->addDays(mt_rand(5, 20));
                } elseif ($rand <= 90) {
                    $stage = $stages->firstWhere('name', 'Rejected');
                    $rejectedAt = $date->copy()->addDays(mt_rand(7, 30));
                } else {
                    $stage = $stages->firstWhere('name', 'Sent');
                }
            } elseif ($daysSince > 14) {
                $rand = mt_rand(1, 100);
                if ($rand <= 30) {
                    $stage = $stages->firstWhere('name', 'Accepted');
                    $acceptedAt = $date->copy()->addDays(mt_rand(5, 14));
                } elseif ($rand <= 40) {
                    $stage = $stages->firstWhere('name', 'Rejected');
                    $rejectedAt = $date->copy()->addDays(mt_rand(5, 14));
                } else {
                    $stage = $stages->firstWhere('name', 'Sent');
                }
            } elseif ($daysSince > 3) {
                $stage = $stages->whereIn('name', ['Draft', 'Sent'])->random();
            }

            if ($acceptedAt && $acceptedAt->gt(now())) {
                $acceptedAt = now();
            }
            if ($rejectedAt && $rejectedAt->gt(now())) {
                $rejectedAt = now();
            }

            $lineItems = $this->generateLineItems(mt_rand(1, 5));
            $subtotal = array_sum(array_column($lineItems, 'amount'));
            $taxRate = 10;
            $tax = round($subtotal * $taxRate / 100, 2);

            // Occasionally apply a discount (20% of quotes)
            $discount = 0;
            if (mt_rand(1, 100) <= 20) {
                $discount = round($subtotal * mt_rand(5, 15) / 100, 2);
            }
            $total = $subtotal - $discount + $tax;

            $quote = Quote::create([
                'title' => $title,
                'description' => 'Standalone quotation for '.$orgName.'. Contact: '.($person->first_name ?? '').' '.($person->last_name ?? ''),
                'reference' => 'REF-'.strtoupper(substr(md5(mt_rand().$i.time()), 0, 8)),
                'person_id' => $person->id,
                'organization_id' => $org->id ?? null,
                'currency' => $this->currency,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'discount' => $discount,
                'adjustments' => 0,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'issue_at' => $date,
                'expire_at' => $date->copy()->addDays(30),
                'accepted_at' => $acceptedAt,
                'rejected_at' => $rejectedAt,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            foreach ($lineItems as $item) {
                QuoteProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'currency' => $this->currency,
                    'tax_rate' => $taxRate,
                ]);
            }

            $this->backdateModel($quote, $date);
            $standaloneCreated++;
        }

        $this->quotes = Quote::with('quoteProducts')->get();
        $accepted = Quote::whereNotNull('accepted_at')->count();
        $rejected = Quote::whereNotNull('rejected_at')->count();
        $this->command->info("  → Created {$this->quotes->count()} quotes ({$accepted} accepted, {$rejected} rejected, {$standaloneCreated} standalone)");
    }

    // =========================================================================
    // Orders
    // =========================================================================

    protected function seedOrders(): void
    {
        $this->command->info('Seeding orders...');

        $pipelineId = $this->getPipelineId(Order::class);
        $stages = $this->getPipelineStages(Order::class);

        // Order stages: 18=Draft, 19=Open, 20=Invoiced, 21=Delivered, 22=Completed

        $acceptedQuotes = Quote::whereNotNull('accepted_at')->with('quoteProducts')->get();
        $orderCount = 0;

        foreach ($acceptedQuotes as $quote) {
            // 85% of accepted quotes become orders
            if (mt_rand(1, 100) > 85) {
                continue;
            }

            $quoteAcceptedAt = $quote->accepted_at;
            $date = $quoteAcceptedAt->copy()->addDays(mt_rand(1, 14));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 3));
            }

            $daysSince = $date->diffInDays(now());
            $stage = $stages->first(); // Draft

            if ($daysSince > 60) {
                $stage = $stages->firstWhere('name', 'Completed') ?? $stages->last();
            } elseif ($daysSince > 30) {
                $stage = $stages->whereIn('name', ['Invoiced', 'Delivered'])->random();
            } elseif ($daysSince > 14) {
                $stage = $stages->firstWhere('name', 'Open');
            }

            $order = Order::create([
                'reference' => 'ORD-'.strtoupper(substr(md5($quote->id.time().$orderCount), 0, 8)),
                'deal_id' => $quote->deal_id,
                'quote_id' => $quote->id,
                'lead_id' => $quote->lead_id ?? null,
                'person_id' => $quote->person_id,
                'organization_id' => $quote->organization_id,
                'currency' => $this->currency,
                'subtotal' => $quote->subtotal / 100, // Convert back from stored cents
                'tax' => $quote->tax / 100,
                'total' => $quote->total / 100,
                'discount' => ($quote->discount ?? 0) / 100,
                'adjustments' => ($quote->adjustments ?? 0) / 100,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create OrderProduct records from QuoteProducts
            foreach ($quote->quoteProducts as $qp) {
                OrderProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'order_id' => $order->id,
                    'product_id' => $qp->product_id,
                    'quote_product_id' => $qp->id,
                    'quantity' => $qp->quantity,
                    'price' => $qp->price / 100, // Convert back from stored cents
                    'amount' => $qp->amount / 100,
                    'currency' => $this->currency,
                    'tax_rate' => $qp->tax_rate ?? 10,
                ]);
            }

            // Mark quote as "Ordered"
            $orderedStage = $this->getPipelineStages(Quote::class)->firstWhere('name', 'Ordered');
            if ($orderedStage) {
                DB::table($quote->getTable())->where('id', $quote->id)->update([
                    'pipeline_stage_id' => $orderedStage->id,
                ]);
            }

            $this->backdateModel($order, $date);
            $orderCount++;
        }

        $this->orders = Order::with('orderProducts')->get();
        $this->command->info("  → Created {$this->orders->count()} orders");
    }

    // =========================================================================
    // Invoices
    // =========================================================================

    protected function seedInvoices(): void
    {
        $this->command->info('Seeding invoices...');

        $pipelineId = $this->getPipelineId(Invoice::class);
        $stages = $this->getPipelineStages(Invoice::class);

        // Invoice stages: 23=Draft, 24=Awaiting Approval, 25=Awaiting Payment, 26=Paid

        $invoiceCount = 0;

        foreach ($this->orders as $order) {
            $orderCreatedAt = Carbon::parse($order->created_at);
            $date = $orderCreatedAt->copy()->addDays(mt_rand(1, 14));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 3));
            }

            $daysSince = $date->diffInDays(now());
            $fullyPaidAt = null;
            $amountPaid = 0;
            $amountDue = $order->total / 100; // Convert from stored cents
            $stage = $stages->first(); // Draft

            if ($daysSince > 45) {
                // Old invoices: most are paid
                $rand = mt_rand(1, 100);
                if ($rand <= 75) {
                    $stage = $stages->firstWhere('name', 'Paid');
                    $fullyPaidAt = $date->copy()->addDays(mt_rand(15, 45));
                    $amountPaid = $amountDue;
                    $amountDue = 0;
                } else {
                    $stage = $stages->firstWhere('name', 'Awaiting Payment');
                }
            } elseif ($daysSince > 14) {
                $rand = mt_rand(1, 100);
                if ($rand <= 40) {
                    $stage = $stages->firstWhere('name', 'Paid');
                    $fullyPaidAt = $date->copy()->addDays(mt_rand(10, 30));
                    $amountPaid = $amountDue;
                    $amountDue = 0;
                } elseif ($rand <= 70) {
                    $stage = $stages->firstWhere('name', 'Awaiting Payment');
                } else {
                    $stage = $stages->firstWhere('name', 'Awaiting Approval');
                }
            }

            if ($fullyPaidAt && $fullyPaidAt->gt(now())) {
                $fullyPaidAt = now();
            }

            $invoice = Invoice::create([
                'reference' => 'INV-'.strtoupper(substr(md5($order->id.time().$invoiceCount), 0, 8)),
                'order_id' => $order->id,
                'person_id' => $order->person_id,
                'organization_id' => $order->organization_id,
                'currency' => $this->currency,
                'subtotal' => $order->subtotal / 100,
                'tax' => $order->tax / 100,
                'total' => $order->total / 100,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
                'issue_date' => $date,
                'due_date' => $date->copy()->addDays(mt_rand(14, 30)),
                'fully_paid_at' => $fullyPaidAt,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create InvoiceLine records from OrderProducts
            foreach ($order->orderProducts as $op) {
                InvoiceLine::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'invoice_id' => $invoice->id,
                    'product_id' => $op->product_id,
                    'order_product_id' => $op->id,
                    'quantity' => $op->quantity,
                    'price' => $op->price / 100,
                    'amount' => $op->amount / 100,
                    'tax_rate' => $op->tax_rate ?? 10,
                    'tax_amount' => round(($op->amount / 100) * (($op->tax_rate ?? 10) / 100), 2),
                    'currency' => $this->currency,
                ]);
            }

            $this->backdateModel($invoice, $date);
            $invoiceCount++;
        }

        $this->invoices = Invoice::all();
        $paidCount = Invoice::whereNotNull('fully_paid_at')->count();
        $this->command->info("  → Created {$this->invoices->count()} invoices ({$paidCount} fully paid)");
    }

    // =========================================================================
    // Deliveries
    // =========================================================================

    protected function seedDeliveries(): void
    {
        $this->command->info('Seeding deliveries...');

        $pipelineId = $this->getPipelineId(Delivery::class);
        $stages = $this->getPipelineStages(Delivery::class);

        // Delivery stages: 27=Draft, 28=Packed, 29=Sent, 30=Delivered

        $deliveryCount = 0;

        foreach ($this->orders as $order) {
            // 75% of orders get a delivery
            if (mt_rand(1, 100) > 75) {
                continue;
            }

            $orderCreatedAt = Carbon::parse($order->created_at);
            $date = $orderCreatedAt->copy()->addDays(mt_rand(3, 21));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 3));
            }

            $daysSince = $date->diffInDays(now());
            $deliveredOn = null;
            $deliveryExpected = $date->copy()->addDays(mt_rand(5, 21));
            $stage = $stages->first(); // Draft

            if ($daysSince > 30) {
                $stage = $stages->firstWhere('name', 'Delivered');
                $deliveredOn = $deliveryExpected->copy()->addDays(mt_rand(-3, 5));
            } elseif ($daysSince > 14) {
                $stage = $stages->firstWhere('name', 'Sent');
            } elseif ($daysSince > 7) {
                $stage = $stages->firstWhere('name', 'Packed');
            }

            if ($deliveredOn && $deliveredOn->gt(now())) {
                $deliveredOn = now();
            }

            $delivery = Delivery::create([
                'reference' => 'DEL-'.strtoupper(substr(md5($order->id.mt_rand()), 0, 8)),
                'order_id' => $order->id,
                'delivery_expected' => $deliveryExpected,
                'delivered_on' => $deliveredOn,
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create DeliveryProduct records from OrderProducts
            foreach ($order->orderProducts as $op) {
                DeliveryProduct::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'delivery_id' => $delivery->id,
                    'order_product_id' => $op->id,
                    'quantity' => $op->quantity,
                ]);
            }

            $this->backdateModel($delivery, $date);
            $deliveryCount++;
        }

        $totalDelivered = Delivery::whereNotNull('delivered_on')->count();
        $this->command->info("  → Created {$deliveryCount} deliveries ({$totalDelivered} delivered)");
    }

    // =========================================================================
    // Purchase Orders
    // =========================================================================

    protected function seedPurchaseOrders(): void
    {
        $this->command->info('Seeding purchase orders...');

        $pipelineId = $this->getPipelineId(PurchaseOrder::class);
        $stages = $this->getPipelineStages(PurchaseOrder::class);

        // PO stages: 31=Draft, 32=Awaiting Approval, 33=Approved, 34=Paid

        $poCount = 0;

        // Create POs for ~40% of orders
        foreach ($this->orders as $order) {
            if (mt_rand(1, 100) > 40) {
                continue;
            }

            $orderCreatedAt = Carbon::parse($order->created_at);
            $date = $orderCreatedAt->copy()->addDays(mt_rand(1, 10));
            if ($date->gt(now())) {
                $date = now()->subDays(mt_rand(1, 3));
            }

            $daysSince = $date->diffInDays(now());
            $stage = $stages->first(); // Draft

            if ($daysSince > 45) {
                $stage = $stages->firstWhere('name', 'Paid');
            } elseif ($daysSince > 21) {
                $stage = $stages->firstWhere('name', 'Approved');
            } elseif ($daysSince > 7) {
                $stage = $stages->firstWhere('name', 'Awaiting Approval');
            }

            // PO amounts are typically cost-based (lower than sale price)
            $subtotal = round(($order->subtotal / 100) * 0.4, 2); // 40% of order subtotal as cost
            $taxRate = 10;
            $tax = round($subtotal * $taxRate / 100, 2);
            $total = $subtotal + $tax;

            // Pick a random supplier organization
            $supplier = $this->organizations->random();

            $po = PurchaseOrder::create([
                'reference' => 'PO-'.strtoupper(substr(md5($order->id.mt_rand()), 0, 8)),
                'order_id' => $order->id,
                'person_id' => $supplier->people->first()->id ?? $order->person_id,
                'organization_id' => $supplier->id,
                'currency' => $this->currency,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'issue_date' => $date,
                'delivery_date' => $date->copy()->addDays(mt_rand(14, 45)),
                'pipeline_id' => $pipelineId,
                'pipeline_stage_id' => $stage->id ?? null,
                'user_created_id' => $this->userId,
                'user_owner_id' => $this->userId,
                'user_assigned_id' => $this->userId,
            ]);

            // Create PO line items
            foreach ($order->orderProducts as $op) {
                $costPrice = ($op->price / 100) * 0.4; // 40% of sale price
                PurchaseOrderLine::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'purchase_order_id' => $po->id,
                    'product_id' => $op->product_id,
                    'quantity' => $op->quantity,
                    'price' => $costPrice,
                    'amount' => round($costPrice * $op->quantity, 2),
                    'tax_rate' => $taxRate,
                    'tax_amount' => round($costPrice * $op->quantity * $taxRate / 100, 2),
                    'currency' => $this->currency,
                ]);
            }

            $this->backdateModel($po, $date);
            $poCount++;
        }

        $this->command->info("  → Created {$poCount} purchase orders");
    }

    // =========================================================================
    // Activities (Tasks, Notes, Calls, Meetings)
    // =========================================================================

    protected function seedActivities(): void
    {
        $this->command->info('Seeding activities (tasks, notes, calls, meetings, lunches)...');

        $allEntities = $this->getAllEntitiesForActivities();

        $this->seedTasks($allEntities);
        $this->seedNotes($allEntities);
        $this->seedCalls($allEntities);
        $this->seedMeetings($allEntities);
        $this->seedLunches($allEntities);
    }

    /**
     * Collect every entity that should receive activities.
     */
    protected function getAllEntitiesForActivities(): Collection
    {
        $allEntities = collect();

        foreach ([Lead::class, Deal::class, Quote::class, Order::class, Invoice::class, Delivery::class, PurchaseOrder::class, Person::class, Organization::class] as $modelClass) {
            $modelClass::select('id', 'created_at')->get()->each(function ($model) use (&$allEntities, $modelClass) {
                $allEntities->push([
                    'type' => $modelClass,
                    'id' => $model->id,
                    'date' => Carbon::parse($model->created_at),
                ]);
            });
        }

        return $allEntities;
    }

    protected function seedTasks(Collection $allEntities): void
    {
        $taskNames = [
            'Follow up with client', 'Send proposal document', 'Schedule product demo',
            'Review contract terms', 'Update CRM records', 'Prepare presentation',
            'Call to discuss pricing', 'Send invoice reminder', 'Check delivery status',
            'Onboarding call', 'Quarterly review meeting', 'Renewal discussion',
            'Technical requirements gathering', 'Send welcome pack', 'License activation',
            'Collect feedback', 'Update project timeline', 'Final approval sign-off',
        ];

        $taskDescriptions = [
            'Please complete this task at your earliest convenience.',
            'High priority — client is waiting for response.',
            'Routine follow-up as per our process.',
            'Part of the standard onboarding workflow.',
            'Ensure this is done before end of week.',
            'Required for compliance and record keeping.',
        ];

        $taskCount = 0;
        foreach ($allEntities as $entity) {
            $numTasks = mt_rand(3, 5);
            for ($i = 0; $i < $numTasks; $i++) {
                $taskDate = $entity['date']->copy()->addDays(mt_rand(1, 90));
                if ($taskDate->gt(now()->addDays(30))) {
                    $taskDate = now()->subDays(mt_rand(1, 60));
                }

                $isCompleted = $taskDate->lt(now()->subDays(3)) && mt_rand(1, 100) <= 80;
                $completedAt = $isCompleted ? $taskDate->copy()->addDays(mt_rand(0, 5))->min(now()) : null;

                $task = Task::create([
                    'name' => $taskNames[array_rand($taskNames)],
                    'description' => $taskDescriptions[array_rand($taskDescriptions)],
                    'due_at' => $taskDate,
                    'completed_at' => $completedAt,
                    'taskable_type' => $entity['type'],
                    'taskable_id' => $entity['id'],
                    'user_created_id' => $this->userId,
                    'user_owner_id' => $this->userId,
                    'user_assigned_id' => $this->userId,
                ]);

                Activity::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'causeable_type' => config('auth.providers.users.model', 'App\Models\User'),
                    'causeable_id' => $this->userId,
                    'timelineable_type' => $entity['type'],
                    'timelineable_id' => $entity['id'],
                    'recordable_type' => Task::class,
                    'recordable_id' => $task->id,
                    'description' => 'Task created',
                ]);

                $createdAt = $taskDate->copy()->min(now());
                $this->backdateModel($task, $createdAt);
                $this->backdateModel($task->activity, $createdAt);
                $taskCount++;
            }
        }

        $this->command->info("  → Created {$taskCount} tasks");
    }

    protected function seedNotes(Collection $allEntities): void
    {
        $noteContents = [
            'Client expressed strong interest in our enterprise solution. They need a proposal by next week.',
            'Had a productive discussion about their requirements. Key decision maker is the CTO.',
            'Price seems to be the main concern. We might need to offer a discount or phased approach.',
            'Client is currently using a competitor product but their contract expires in 3 months.',
            'Very positive meeting. They want to move forward quickly. Need to schedule technical review.',
            'Waiting on client to provide their technical specifications and integration requirements.',
            'Client budget has been approved. They are ready to proceed once we finalize the contract.',
            'Need to follow up — client mentioned they are also evaluating two other vendors.',
            'Spoke with procurement team. They need 3 references from similar-sized companies.',
            'Good call today. Agreed on timeline. Implementation to start next month.',
            'Client raised some concerns about data migration complexity. Scheduled a deep-dive session.',
            'Successful demo. The team was impressed with the reporting features.',
            'Annual review completed. Client is happy with the service and considering expansion.',
            'Left voicemail. Will try again tomorrow morning.',
            'Email sent with updated pricing matrix and service level agreement.',
        ];

        $noteCount = 0;
        foreach ($allEntities as $entity) {
            $numNotes = mt_rand(3, 5);
            for ($i = 0; $i < $numNotes; $i++) {
                $noteDate = $entity['date']->copy()->addDays(mt_rand(0, 90));
                if ($noteDate->gt(now())) {
                    $noteDate = now()->subDays(mt_rand(1, 14));
                }

                $note = Note::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'content' => $noteContents[array_rand($noteContents)],
                    'noted_at' => $noteDate,
                    'noteable_type' => $entity['type'],
                    'noteable_id' => $entity['id'],
                    'user_created_id' => $this->userId,
                ]);

                Activity::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'causeable_type' => config('auth.providers.users.model', 'App\Models\User'),
                    'causeable_id' => $this->userId,
                    'timelineable_type' => $entity['type'],
                    'timelineable_id' => $entity['id'],
                    'recordable_type' => Note::class,
                    'recordable_id' => $note->id,
                    'description' => 'Note added',
                ]);

                $this->backdateModel($note, $noteDate);
                $this->backdateModel($note->activity, $noteDate);
                $noteCount++;
            }
        }

        $this->command->info("  → Created {$noteCount} notes");
    }

    protected function seedCalls(Collection $allEntities): void
    {
        $callNames = [
            'Discovery call', 'Follow-up call', 'Pricing discussion', 'Technical review',
            'Contract negotiation', 'Quarterly check-in', 'Support escalation', 'Intro call',
            'Demo follow-up', 'Renewal call', 'Cold call', 'Referral call',
        ];

        $callCount = 0;
        foreach ($allEntities as $entity) {
            $numCalls = mt_rand(3, 5);
            for ($i = 0; $i < $numCalls; $i++) {
                $callDate = $entity['date']->copy()->addDays(mt_rand(1, 60));
                if ($callDate->gt(now())) {
                    $callDate = now()->subDays(mt_rand(1, 14));
                }

                $duration = mt_rand(5, 60);
                $startAt = $callDate->copy()->setTime($this->randomBiasedInt(9, 17), mt_rand(0, 59));
                $finishAt = $startAt->copy()->addMinutes($duration);

                $call = Call::create([
                    'name' => $callNames[array_rand($callNames)],
                    'description' => 'Call lasting approximately '.$duration.' minutes.',
                    'start_at' => $startAt,
                    'finish_at' => $finishAt,
                    'callable_type' => $entity['type'],
                    'callable_id' => $entity['id'],
                    'user_created_id' => $this->userId,
                    'user_owner_id' => $this->userId,
                    'user_assigned_id' => $this->userId,
                ]);

                Activity::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'causeable_type' => config('auth.providers.users.model', 'App\Models\User'),
                    'causeable_id' => $this->userId,
                    'timelineable_type' => $entity['type'],
                    'timelineable_id' => $entity['id'],
                    'recordable_type' => Call::class,
                    'recordable_id' => $call->id,
                    'description' => 'Call logged',
                ]);

                $this->backdateModel($call, $callDate);
                $this->backdateModel($call->activity, $callDate);
                $callCount++;
            }
        }

        $this->command->info("  → Created {$callCount} calls");
    }

    protected function seedMeetings(Collection $allEntities): void
    {
        $meetingNames = [
            'Initial discovery meeting', 'Product demo', 'Requirements workshop',
            'Contract review meeting', 'Technical architecture session', 'Kick-off meeting',
            'Quarterly business review', 'Strategy alignment', 'Executive briefing',
            'Implementation planning', 'Training session', 'Go-live preparation',
        ];

        $meetingLocations = [
            'Conference Room A', 'Virtual - Zoom', 'Virtual - Teams', 'Virtual - Google Meet',
            'Client Office', 'Board Room', 'Meeting Room 2', 'Offsite Venue',
        ];

        $meetingCount = 0;
        foreach ($allEntities as $entity) {
            $numMeetings = mt_rand(3, 5);
            for ($i = 0; $i < $numMeetings; $i++) {
                $meetingDate = $entity['date']->copy()->addDays(mt_rand(3, 90));
                if ($meetingDate->gt(now()->addDays(14))) {
                    $meetingDate = now()->subDays(mt_rand(1, 30));
                }

                $startHour = $this->randomBiasedInt(9, 16);
                $duration = [30, 60, 90, 120][array_rand([30, 60, 90, 120])];
                $startAt = $meetingDate->copy()->setTime($startHour, (mt_rand(0, 1) === 0 ? 0 : 30));
                $finishAt = $startAt->copy()->addMinutes($duration);

                $meeting = Meeting::create([
                    'name' => $meetingNames[array_rand($meetingNames)],
                    'description' => 'Meeting at '.$meetingLocations[array_rand($meetingLocations)].'. Duration: '.$duration.' minutes.',
                    'start_at' => $startAt,
                    'finish_at' => $finishAt,
                    'meetingable_type' => $entity['type'],
                    'meetingable_id' => $entity['id'],
                    'user_created_id' => $this->userId,
                    'user_owner_id' => $this->userId,
                    'user_assigned_id' => $this->userId,
                ]);

                Activity::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'causeable_type' => config('auth.providers.users.model', 'App\Models\User'),
                    'causeable_id' => $this->userId,
                    'timelineable_type' => $entity['type'],
                    'timelineable_id' => $entity['id'],
                    'recordable_type' => Meeting::class,
                    'recordable_id' => $meeting->id,
                    'description' => 'Meeting scheduled',
                ]);

                $createdAt = $meetingDate->copy()->min(now());
                $this->backdateModel($meeting, $createdAt);
                $this->backdateModel($meeting->activity, $createdAt);
                $meetingCount++;
            }
        }

        $this->command->info("  → Created {$meetingCount} meetings");
    }

    protected function seedLunches(Collection $allEntities): void
    {
        $lunchNames = [
            'Client lunch', 'Business lunch', 'Relationship building lunch',
            'Partnership discussion lunch', 'Networking lunch', 'Team lunch with client',
            'Welcome lunch', 'Celebration lunch', 'Strategy lunch', 'Vendor lunch',
        ];

        $lunchLocations = [
            'The Capital Grille', 'Nobu Restaurant', 'Local Bistro', 'Hotel Restaurant',
            'Client Office Cafeteria', 'Downtown Deli', 'Italian Trattoria', 'Steakhouse',
            'Sushi Bar', 'French Café',
        ];

        $lunchCount = 0;
        foreach ($allEntities as $entity) {
            $numLunches = mt_rand(3, 5);
            for ($i = 0; $i < $numLunches; $i++) {
                $lunchDate = $entity['date']->copy()->addDays(mt_rand(3, 90));
                if ($lunchDate->gt(now()->addDays(14))) {
                    $lunchDate = now()->subDays(mt_rand(1, 30));
                }

                $duration = [60, 90, 120][array_rand([60, 90, 120])];
                $startAt = $lunchDate->copy()->setTime($this->randomBiasedInt(11, 13), (mt_rand(0, 1) === 0 ? 0 : 30));
                $finishAt = $startAt->copy()->addMinutes($duration);
                $location = $lunchLocations[array_rand($lunchLocations)];

                $lunch = Lunch::create([
                    'name' => $lunchNames[array_rand($lunchNames)],
                    'description' => 'Lunch at '.$location.'. Duration: '.$duration.' minutes.',
                    'start_at' => $startAt,
                    'finish_at' => $finishAt,
                    'location' => $location,
                    'lunchable_type' => $entity['type'],
                    'lunchable_id' => $entity['id'],
                    'user_created_id' => $this->userId,
                    'user_owner_id' => $this->userId,
                    'user_assigned_id' => $this->userId,
                ]);

                Activity::create([
                    'external_id' => Uuid::uuid4()->toString(),
                    'causeable_type' => config('auth.providers.users.model', 'App\Models\User'),
                    'causeable_id' => $this->userId,
                    'timelineable_type' => $entity['type'],
                    'timelineable_id' => $entity['id'],
                    'recordable_type' => Lunch::class,
                    'recordable_id' => $lunch->id,
                    'description' => 'Lunch scheduled',
                ]);

                $createdAt = $lunchDate->copy()->min(now());
                $this->backdateModel($lunch, $createdAt);
                $this->backdateModel($lunch->activity, $createdAt);
                $lunchCount++;
            }
        }

        $this->command->info("  → Created {$lunchCount} lunches");
    }

    // =========================================================================
    // Labels
    // =========================================================================

    protected function seedLabels(): void
    {
        $this->command->info('Attaching labels to entities...');

        $labels = Label::all();

        if ($labels->isEmpty()) {
            $this->command->info('  → No labels found, skipping');

            return;
        }

        $attached = 0;

        // Attach labels to leads (40%)
        Lead::inRandomOrder()->take((int) (Lead::count() * 0.4))->get()->each(function ($lead) use ($labels, &$attached) {
            $lead->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(3, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to deals (35%)
        Deal::inRandomOrder()->take((int) (Deal::count() * 0.35))->get()->each(function ($deal) use ($labels, &$attached) {
            $deal->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to people (20%)
        Person::inRandomOrder()->take((int) (Person::count() * 0.2))->get()->each(function ($person) use ($labels, &$attached) {
            $person->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to organizations (25%)
        Organization::inRandomOrder()->take((int) (Organization::count() * 0.25))->get()->each(function ($org) use ($labels, &$attached) {
            $org->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to quotes (25%)
        Quote::inRandomOrder()->take((int) (Quote::count() * 0.25))->get()->each(function ($quote) use ($labels, &$attached) {
            $quote->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to orders (20%)
        Order::inRandomOrder()->take((int) (Order::count() * 0.2))->get()->each(function ($order) use ($labels, &$attached) {
            $order->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to invoices (15%)
        Invoice::inRandomOrder()->take((int) (Invoice::count() * 0.15))->get()->each(function ($invoice) use ($labels, &$attached) {
            $invoice->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        // Attach labels to purchase orders (15%)
        PurchaseOrder::inRandomOrder()->take((int) (PurchaseOrder::count() * 0.15))->get()->each(function ($po) use ($labels, &$attached) {
            $po->labels()->syncWithoutDetaching(
                $labels->random(mt_rand(1, min(2, $labels->count())))->pluck('id')->toArray()
            );
            $attached++;
        });

        $this->command->info("  → Attached labels to {$attached} entities");
    }

    // =========================================================================
    // Custom Field Groups & Fields
    // =========================================================================

    protected function seedCustomFieldGroups(): void
    {
        $this->command->info('Seeding custom field groups & fields...');

        $groups = [
            [
                'name' => 'Lead Qualification',
                'fields' => [
                    ['type' => 'select',   'name' => 'Lead Source Channel', 'options' => [['value' => 'website', 'label' => 'Website'], ['value' => 'referral', 'label' => 'Referral'], ['value' => 'event', 'label' => 'Event'], ['value' => 'cold_outreach', 'label' => 'Cold Outreach']]],
                    ['type' => 'text',     'name' => 'Competitor Mentioned'],
                    ['type' => 'checkbox', 'name' => 'Decision Maker Contacted', 'default' => '0'],
                    ['type' => 'date',     'name' => 'Initial Contact Date'],
                    ['type' => 'textarea', 'name' => 'Qualification Notes'],
                ],
                'models' => ['VentureDrake\LaravelCrm\Models\Lead'],
            ],
            [
                'name' => 'Deal Details',
                'fields' => [
                    ['type' => 'text',              'name' => 'Contract Reference'],
                    ['type' => 'select',            'name' => 'Deal Priority', 'options' => [['value' => 'low', 'label' => 'Low'], ['value' => 'medium', 'label' => 'Medium'], ['value' => 'high', 'label' => 'High'], ['value' => 'critical', 'label' => 'Critical']]],
                    ['type' => 'checkbox',          'name' => 'NDA Signed', 'default' => '0'],
                    ['type' => 'date',              'name' => 'Expected Close Date'],
                    ['type' => 'checkbox_multiple', 'name' => 'Products of Interest', 'options' => [['value' => 'software', 'label' => 'Software'], ['value' => 'hardware', 'label' => 'Hardware'], ['value' => 'services', 'label' => 'Professional Services'], ['value' => 'support', 'label' => 'Support Contract']]],
                ],
                'models' => ['VentureDrake\LaravelCrm\Models\Deal'],
            ],
            [
                'name' => 'Contact Profile',
                'fields' => [
                    ['type' => 'select',   'name' => 'Preferred Contact Method', 'options' => [['value' => 'email', 'label' => 'Email'], ['value' => 'phone', 'label' => 'Phone'], ['value' => 'sms', 'label' => 'SMS'], ['value' => 'video', 'label' => 'Video Call']]],
                    ['type' => 'text',     'name' => 'LinkedIn Profile URL'],
                    ['type' => 'radio',    'name' => 'Communication Frequency', 'options' => [['value' => 'weekly', 'label' => 'Weekly'], ['value' => 'monthly', 'label' => 'Monthly'], ['value' => 'quarterly', 'label' => 'Quarterly']]],
                    ['type' => 'checkbox', 'name' => 'Newsletter Subscriber', 'default' => '0'],
                    ['type' => 'textarea', 'name' => 'Personal Notes'],
                ],
                'models' => ['VentureDrake\LaravelCrm\Models\Person', 'VentureDrake\LaravelCrm\Models\Organization'],
            ],
            [
                'name' => 'Company Information',
                'fields' => [
                    ['type' => 'select',   'name' => 'Industry Sector', 'options' => [['value' => 'tech', 'label' => 'Technology'], ['value' => 'finance', 'label' => 'Finance'], ['value' => 'healthcare', 'label' => 'Healthcare'], ['value' => 'retail', 'label' => 'Retail'], ['value' => 'manufacturing', 'label' => 'Manufacturing'], ['value' => 'other', 'label' => 'Other']]],
                    ['type' => 'text',     'name' => 'Company Registration Number'],
                    ['type' => 'radio',    'name' => 'Company Size', 'options' => [['value' => '1_10', 'label' => '1–10'], ['value' => '11_50', 'label' => '11–50'], ['value' => '51_200', 'label' => '51–200'], ['value' => '201_plus', 'label' => '201+']]],
                    ['type' => 'checkbox', 'name' => 'Publicly Listed', 'default' => '0'],
                    ['type' => 'date',     'name' => 'Relationship Since'],
                ],
                'models' => ['VentureDrake\LaravelCrm\Models\Organization'],
            ],
            [
                'name' => 'Project & Delivery',
                'fields' => [
                    ['type' => 'text',     'name' => 'Project Code'],
                    ['type' => 'select',   'name' => 'Delivery Region', 'options' => [['value' => 'north_america', 'label' => 'North America'], ['value' => 'europe', 'label' => 'Europe'], ['value' => 'apac', 'label' => 'Asia Pacific'], ['value' => 'latam', 'label' => 'Latin America'], ['value' => 'mea', 'label' => 'MEA']]],
                    ['type' => 'date',     'name' => 'Promised Delivery Date'],
                    ['type' => 'checkbox', 'name' => 'Express Shipping', 'default' => '0'],
                    ['type' => 'textarea', 'name' => 'Special Instructions'],
                ],
                'models' => ['VentureDrake\LaravelCrm\Models\Quote', 'VentureDrake\LaravelCrm\Models\Order', 'VentureDrake\LaravelCrm\Models\Invoice'],
            ],
        ];

        $total = 0;

        foreach ($groups as $groupData) {
            $group = FieldGroup::create([
                'external_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name'        => $groupData['name'],
            ]);

            foreach ($groupData['fields'] as $order => $fieldData) {
                $field = Field::create([
                    'external_id'    => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'field_group_id' => $group->id,
                    'type'           => $fieldData['type'],
                    'name'           => $fieldData['name'],
                    'required'       => false,
                    'default'        => $fieldData['default'] ?? null,
                ]);

                if (isset($fieldData['options'])) {
                    foreach ($fieldData['options'] as $i => $opt) {
                        FieldOption::create([
                            'external_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                            'field_id'    => $field->id,
                            'value'       => $opt['value'],
                            'label'       => $opt['label'],
                            'order'       => $i + 1,
                        ]);
                    }
                }

                foreach ($groupData['models'] as $model) {
                    FieldModel::firstOrCreate([
                        'field_id' => $field->id,
                        'model'    => $model,
                    ]);
                }

                $total++;
            }
        }

        $this->command->info("  → Created ".count($groups)." custom field groups with {$total} fields");
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate realistic line items from the product catalog.
     */
    protected function generateLineItems(int $count): array
    {
        $items = [];
        $usedProductIds = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a product we haven't used yet
            $product = $this->products->whereNotIn('id', $usedProductIds)->random();
            $usedProductIds[] = $product->id;

            $price = $product->productPrices->first();
            $unitPrice = $price ? $price->unit_price / 100 : $this->randomAmount(100, 5000); // Convert from stored cents
            $quantity = mt_rand(1, 5);

            // Occasionally give a small quantity discount
            $adjustedPrice = $unitPrice;
            if ($quantity >= 3 && mt_rand(1, 100) <= 30) {
                $adjustedPrice = round($unitPrice * 0.9, 2); // 10% volume discount
            }

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $adjustedPrice,
                'amount' => round($adjustedPrice * $quantity, 2),
            ];

            if (count($usedProductIds) >= $this->products->count()) {
                break;
            }
        }

        return $items;
    }
}
