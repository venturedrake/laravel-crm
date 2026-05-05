<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelCrmChatTables extends Migration
{
    public function up()
    {
        Schema::create(config('laravel-crm.db_table_prefix').'chat_widgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('public_key', 64)->unique();   // used in embed snippet
            $table->string('name');
            $table->string('welcome_message')->nullable();
            $table->string('color', 16)->default('#2563eb');
            $table->string('position', 16)->default('bottom-right'); // bottom-right | bottom-left
            $table->json('allowed_origins')->nullable(); // optional CORS allow-list
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(config('laravel-crm.db_table_prefix').'chat_visitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('chat_widget_id')->index();
            $table->string('visitor_token', 64)->unique(); // cookie/localStorage on visitor browser
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('current_url', 1024)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('person_id')->nullable()->index(); // promoted to CRM Person
            $table->timestamps();
        });

        Schema::create(config('laravel-crm.db_table_prefix').'chat_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('chat_id')->nullable(); // human ID like C1001
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('chat_widget_id')->index();
            $table->unsignedBigInteger('chat_visitor_id')->index();
            $table->string('subject')->nullable();
            $table->enum('status', ['open', 'pending', 'closed'])->default('open');
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(config('laravel-crm.db_table_prefix').'chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->unsignedBigInteger('chat_conversation_id')->index();
            // sender is polymorphic: either a User (CRM agent) or ChatVisitor
            $table->string('sender_type'); // 'user' | 'visitor' | 'system'
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['chat_conversation_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'chat_messages');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'chat_conversations');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'chat_visitors');
        Schema::dropIfExists(config('laravel-crm.db_table_prefix').'chat_widgets');
    }
}

