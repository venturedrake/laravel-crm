<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisitorReadAtToLaravelCrmChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'chat_messages', function (Blueprint $table) {
            // Tracks when the *visitor* has seen an agent-sent message (sender_type = 'user')
            $table->timestamp('visitor_read_at')->nullable()->after('read_at');
        });
    }

    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'chat_messages', function (Blueprint $table) {
            $table->dropColumn('visitor_read_at');
        });
    }
}

