<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeadIdToLaravelCrmChatConversationsTable extends Migration
{
    public function up()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'chat_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->index()->after('user_assigned_id');
        });
    }

    public function down()
    {
        Schema::table(config('laravel-crm.db_table_prefix').'chat_conversations', function (Blueprint $table) {
            $table->dropColumn('lead_id');
        });
    }
}

