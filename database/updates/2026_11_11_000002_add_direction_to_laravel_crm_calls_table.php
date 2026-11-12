<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Whether a logged call was made by the team or received from the contact.
 * Calls logged before this were assumed to be made by the person logging
 * them, so they default to outbound.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('laravel-crm.db_table_prefix').'calls';

        if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'direction')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('direction', 10)->default('outbound')->after('location');
            });
        }
    }

    public function down(): void
    {
        $table = config('laravel-crm.db_table_prefix').'calls';

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'direction')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('direction');
            });
        }
    }
};
