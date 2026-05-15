<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailingListToUsersTable extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'mailing_list')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('mailing_list')->default(true)->after('last_online_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'mailing_list')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mailing_list');
            });
        }
    }
}

