<?php

namespace VentureDrake\LaravelCRM\Tests;

use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmSampleDataSeeder;
use VentureDrake\LaravelCrm\Models\Quote;

class QuoteTotalsSeederTest extends \VentureDrake\LaravelCRM\Tests\TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [
            \Livewire\LivewireServiceProvider::class,
        ]);
    }

    public static function setUpBeforeClass(): void
    {
        if (! class_exists('App\\Models\\User')) {
            eval('namespace App\\Models; class User extends \\Illuminate\\Foundation\\Auth\\User { protected $guarded = []; protected $table = "users"; }');
        }
        parent::setUpBeforeClass();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', \App\Models\User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('permission', require __DIR__.'/../config/permission.php');

        // Create the users table first (the package migration alters it).
        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        parent::getEnvironmentSetUp($app);

        // Run every other migration stub in the package so the full schema exists.
        $stubs = glob(__DIR__.'/../database/migrations/*.php.stub');
        // Sort: create_* first, then everything else, alphabetically within group.
        usort($stubs, function ($a, $b) {
            $ac = str_contains(basename($a), 'create_') ? 0 : 1;
            $bc = str_contains(basename($b), 'create_') ? 0 : 1;
            if ($ac !== $bc) {
                return $ac <=> $bc;
            }

            return strcmp(basename($a), basename($b));
        });
        foreach ($stubs as $stub) {
            if (str_contains($stub, 'create_laravel_crm_tables.php.stub')) {
                continue; // Already loaded by parent setup.
            }
            if (str_contains($stub, 'create_permission_tables.php.stub')) {
                continue; // Spatie permissions tables not needed for this test.
            }
            $declaredBefore = get_declared_classes();
            include_once $stub;
            $declaredAfter = get_declared_classes();
            $new = array_diff($declaredAfter, $declaredBefore);
            foreach ($new as $cls) {
                if (method_exists($cls, 'up')) {
                    try {
                        (new $cls())->up();
                    } catch (\Throwable $e) {
                        // Ignore migrations that fail in this minimal harness;
                        // we only care that the tables required by the seeder exist.
                    }
                }
            }
        }
    }

    /** @test */
    public function quote_totals_match_after_seeding()
    {
        // Ensure prefix settings exist (normally created by middleware/seeder).
        foreach ([
            'lead_prefix' => 'LD-',
            'deal_prefix' => 'DL-',
            'quote_prefix' => 'QU-',
            'order_prefix' => 'ORD-',
            'invoice_prefix' => 'INV-',
            'delivery_prefix' => 'DEL-',
            'purchase_order_prefix' => 'PO-',
            'date_format' => 'Y-m-d',
            'tax_name' => 'Tax',
            'tax_rate' => '10',
        ] as $name => $value) {
            \VentureDrake\LaravelCrm\Models\Setting::firstOrCreate(['name' => $name], ['value' => $value]);
        }

        $runSeed = function () {
            $seeder = new LaravelCrmSampleDataSeeder();
            $r = new \ReflectionClass($seeder);
            $faker = $r->getProperty('faker');
            $faker->setAccessible(true);
            $faker->setValue($seeder, \Faker\Factory::create());
            $df = $r->getProperty('dateFormat');
            $df->setAccessible(true);
            $df->setValue($seeder, 'Y-m-d');
            foreach (['seedUsers','seedTeams','seedTaxRates','seedLookups','seedProductCatalogue','seedCustomFields','seedOrganisationsAndPeople','seedClients','seedContacts','seedLeadsAndDeals','seedDealProducts','seedQuotes'] as $m) {
                $method = $r->getMethod($m);
                $method->setAccessible(true);
                $method->invoke($seeder);
            }
        };

        $runSeed();

        // Simulate a `--fresh` reseed: truncate only the parent tables that the
        // sample-data console command currently lists. This reproduces the
        // user-reported bug where line-item tables (quote_products etc.) are
        // left behind and become attached to brand-new parent IDs after a
        // re-seed, throwing every header total out of balance.
        $cmd = new \VentureDrake\LaravelCrm\Console\LaravelCrmSampleData();
        $reflection = new \ReflectionClass($cmd);
        $tablesProp = $reflection->getProperty('sampleTables');
        $tablesProp->setAccessible(true);
        $sampleTables = $tablesProp->getValue($cmd);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        foreach ($sampleTables as $t) {
            $name = config('laravel-crm.db_table_prefix').$t;
            if (\Illuminate\Support\Facades\Schema::hasTable($name)) {
                \Illuminate\Support\Facades\DB::table($name)->truncate();
            }
        }
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $runSeed();

        $bad = [];
        $qpCount = \VentureDrake\LaravelCrm\Models\QuoteProduct::count();
        $qCount = Quote::count();
        echo "\n[debug] quotes={$qCount} quote_products={$qpCount}\n";
        foreach (Quote::with('quoteProducts')->get() as $quote) {
            $sub = 0; $tax = 0;
            foreach ($quote->quoteProducts as $qp) {
                $sub += $qp->quantity * $qp->price;
                $tax += $qp->tax_amount;
                if ($qp->price * $qp->quantity != $qp->amount) {
                    $bad[] = "QP {$qp->id} line: price*qty={$qp->price}*{$qp->quantity} amount={$qp->amount}";
                }
            }
            if ($quote->subtotal != $sub) {
                $bad[] = "Q {$quote->id} subtotal {$quote->subtotal} vs sum {$sub}";
            }
            if ($quote->tax != $tax) {
                $bad[] = "Q {$quote->id} tax {$quote->tax} vs sum {$tax}";
            }
            $expectedTotal = $sub - $quote->discount + $quote->tax + $quote->adjustments;
            if ($quote->total != $expectedTotal) {
                $bad[] = "Q {$quote->id} total {$quote->total} vs expected {$expectedTotal}";
            }
        }

        $this->assertEmpty($bad, implode("\n", array_slice($bad, 0, 20)));
    }
}




















