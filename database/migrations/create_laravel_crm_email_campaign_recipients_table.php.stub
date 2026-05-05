<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmEmailCampaignRecipientsTable extends Migration
{
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'email_campaign_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('email_campaign_id')->index();
            $table->unsignedBigInteger('email_id')->index();
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->string('address');
            $table->string('tracking_token', 64)->unique();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced', 'skipped'])->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->unsignedInteger('opens_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['email_campaign_id', 'email_id'], 'crm_ecr_campaign_email_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'email_campaign_recipients');
    }
}
