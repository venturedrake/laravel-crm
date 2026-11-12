<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Email campaigns used to target every subscribed person. These columns
     * store the audience the user picks. Existing rows default to "all", so
     * they keep the old behaviour.
     */
    public function up(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        Schema::table($name, function (Blueprint $table) use ($name) {
            if (! Schema::hasColumn($name, 'audience_type')) {
                $table->string('audience_type', 32)->default('all')->after('email_template_id');
            }

            if (! Schema::hasColumn($name, 'audience_ids')) {
                $table->json('audience_ids')->nullable()->after('audience_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $name = $this->tableName();

        if (! Schema::hasTable($name)) {
            return;
        }

        $columns = array_values(array_filter(
            ['audience_type', 'audience_ids'],
            fn ($column) => Schema::hasColumn($name, $column)
        ));

        if ($columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    protected function tableName(): string
    {
        return config('laravel-crm.db_table_prefix').'email_campaigns';
    }
};
