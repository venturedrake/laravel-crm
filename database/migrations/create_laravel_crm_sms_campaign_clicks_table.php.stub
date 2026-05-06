<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmSmsCampaignClicksTable extends Migration
{
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'sms_campaign_clicks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sms_campaign_recipient_id')->index();
            $table->text('original_url');
            $table->timestamp('clicked_at');
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'sms_campaign_clicks');
    }
}
