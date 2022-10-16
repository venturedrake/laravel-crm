<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXeroTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xero_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->text('id_token')->nullable();
            $table->text('access_token');
            $table->string('expires_in')->nullable();
            $table->string('token_type')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('scopes');
            $table->string('auth_event_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('tenant_type')->nullable();
            $table->string('tenant_name')->nullable();
            $table->string('created_date_utc')->nullable();
            $table->string('updated_date_utc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xero_tokens');
    }
}
