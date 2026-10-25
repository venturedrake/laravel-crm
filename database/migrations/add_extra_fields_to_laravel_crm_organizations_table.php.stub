<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToLaravelCrmOrganizationsTable extends Migration
{
    /**
     * The columns this migration adds. A v1 host already has them from
     * add_extra_fields_to_laravel_crm_organisations_table, so each is only
     * added when it is missing.
     *
     * @return array<string, Closure>
     */
    protected function columns(): array
    {
        return [
            'vat_number' => fn (Blueprint $table) => $table->string('vat_number')->nullable(),
            'domain' => fn (Blueprint $table) => $table->string('domain')->nullable(),
            'website_url' => fn (Blueprint $table) => $table->string('website_url')->nullable(),
            'year_founded' => fn (Blueprint $table) => $table->smallInteger('year_founded')->nullable(),
            'timezone_id' => fn (Blueprint $table) => $table->unsignedBigInteger('timezone_id')->index()->nullable(),
            'annual_revenue' => fn (Blueprint $table) => $table->integer('annual_revenue')->nullable(),
            'total_money_raised' => fn (Blueprint $table) => $table->bigInteger('total_money_raised')->nullable(),
            'number_of_employees' => fn (Blueprint $table) => $table->smallInteger('number_of_employees')->nullable(),
            'linkedin' => fn (Blueprint $table) => $table->string('linkedin')->nullable(),
            'facebook' => fn (Blueprint $table) => $table->string('facebook')->nullable(),
            'twitter' => fn (Blueprint $table) => $table->string('twitter')->nullable(),
            'instagram' => fn (Blueprint $table) => $table->string('instagram')->nullable(),
            'youtube' => fn (Blueprint $table) => $table->string('youtube')->nullable(),
            'pinterest' => fn (Blueprint $table) => $table->string('pinterest')->nullable(),
            'tiktok' => fn (Blueprint $table) => $table->string('tiktok')->nullable(),
            'google' => fn (Blueprint $table) => $table->string('google')->nullable(),
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('laravel-crm.db_table_prefix').'organizations';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($this->columns() as $column => $definition) {
            if (! Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, $definition);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('laravel-crm.db_table_prefix').'organizations';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        $columns = array_values(array_filter(
            array_keys($this->columns()),
            fn ($column) => Schema::hasColumn($tableName, $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
}
