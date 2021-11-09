<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginsTable extends Migration
{
    public function up()
    {
        Schema::create('logins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip_address');
            $table->string('type')->default(\Lab404\AuthChecker\Models\Login::TYPE_LOGIN);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('device_id')->unsigned()->index()->nullable();
            $table->timestamps();

            // $table->index(['user_id', 'user_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('logins');
    }
}
