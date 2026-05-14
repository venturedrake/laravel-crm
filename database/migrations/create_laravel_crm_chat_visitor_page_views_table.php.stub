<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmChatVisitorPageViewsTable extends Migration
{
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'chat_visitor_page_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('chat_visitor_id')->index();
            $table->string('url', 2048);
            $table->string('title', 512)->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['chat_visitor_id', 'viewed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'chat_visitor_page_views');
    }
}

