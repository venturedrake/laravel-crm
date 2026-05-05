<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscribedToLaravelCrmEmailsTable extends Migration
{
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
            $table->boolean('subscribed')->default(true)->after('primary');
            $table->timestamp('unsubscribed_at')->nullable()->after('subscribed');
        });
    }

    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'emails', function (Blueprint $table) {
            $table->dropColumn(['subscribed', 'unsubscribed_at']);
        });
    }
}
