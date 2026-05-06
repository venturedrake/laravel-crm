<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmSmsCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'sms_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('campaign_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->text('body');
            $table->string('from')->nullable();
            $table->unsignedBigInteger('sms_template_id')->nullable()->index();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled', 'failed'])->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('timezone')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('unique_clicks_count')->default(0);
            $table->unsignedInteger('unsubscribes_count')->default(0);
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'sms_campaigns');
    }
}
