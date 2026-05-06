<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscribedToLaravelCrmPhonesTable extends Migration
{
    public function up()
    {
        // Defaults to false: existing phone numbers are NOT auto-opted into SMS marketing
        // on install. Tenants must record explicit consent (TCPA / GDPR / Australian Spam
        // Act) before flipping `subscribed` to true. New Phone records can be created
        // with `subscribed => true` only when consent is captured at the source (form,
        // checkbox, double opt-in, etc.).
        Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
            $table->boolean('subscribed')->default(false)->after('primary');
            $table->timestamp('unsubscribed_at')->nullable()->after('subscribed');
        });
    }

    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'phones', function (Blueprint $table) {
            $table->dropColumn(['subscribed', 'unsubscribed_at']);
        });
    }
}
