<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An email message is one email logged against a record (person,
     * organisation, lead, deal, …), the way a call or a note is. It is not
     * the `emails` table, which holds contact email addresses.
     *
     * Addresses, subject and body are encryptable, so they are text columns:
     * ciphertext is far longer than the value it holds.
     *
     * @return void
     */
    public function up()
    {
        $prefix = config('laravel-crm.db_table_prefix');

        if (Schema::hasTable($prefix.'email_messages')) {
            return;
        }

        Schema::create($prefix.'email_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->nullableMorphs('emailable');
            $table->string('direction', 10)->default('outbound');
            $table->string('source', 30)->default('manual');
            $table->text('from_address')->nullable();
            $table->text('from_name')->nullable();
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->text('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->text('attachment_names')->nullable();
            $table->dateTime('sent_at')->nullable()->index();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'email_messages');
    }
};
