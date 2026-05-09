<?php

namespace VentureDrake\LaravelCrm\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestSchema
{
    public static function up(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->boolean('crm_access')->default(true);
                $table->timestamp('last_online_at')->nullable();
                $table->unsignedBigInteger('current_crm_team_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::create($prefix.'settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->boolean('global')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'organizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'people', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('title')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('maiden_name')->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('address');
            $table->boolean('primary')->default(false);
            $table->string('type')->default('work')->nullable();
            $table->morphs('emailable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'phones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('number');
            $table->boolean('primary')->default(false);
            $table->string('type')->default('work')->nullable();
            $table->morphs('phoneable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('line')->nullable();
            $table->string('line1')->nullable();
            $table->string('line2')->nullable();
            $table->string('line3')->nullable();
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->morphs('addressable');
            $table->boolean('primary')->default(false);
            $table->unsignedBigInteger('address_type_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('phone_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'lead_statuses', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->smallInteger('order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'lead_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'pipelines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('model');
            $table->boolean('default')->default(false);
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'pipeline_stages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('pipeline_id');
            $table->integer('order')->default(0);
            $table->integer('probability')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('lead_id')->nullable();
            $table->string('prefix')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('lead_status_id')->nullable();
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->integer('pipeline_order')->nullable();
            $table->boolean('qualified')->default(false);
            $table->datetime('expected_close')->nullable();
            $table->datetime('converted_at')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'deals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('deal_id')->nullable();
            $table->string('prefix')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->integer('pipeline_order')->nullable();
            $table->boolean('qualified')->default(false);
            $table->datetime('expected_close')->nullable();
            $table->datetime('closed_at')->nullable();
            $table->string('closed_status')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'labels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('hex')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'labelables', function (Blueprint $table) use ($prefix) {
            $table->bigInteger('label_id');
            $table->bigInteger($prefix.'labelable_id');
            $table->string($prefix.'labelable_type');
        });

        Schema::create($prefix.'notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->text('content');
            $table->morphs('noteable');
            $table->boolean('pinned')->default(false);
            $table->datetime('noted_at')->nullable();
            $table->unsignedBigInteger('related_note_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('log_name')->default('default');
            $table->string('description')->nullable();
            $table->nullableMorphs('causeable');
            $table->nullableMorphs('timelineable');
            $table->nullableMorphs('recordable');
            $table->string('event')->nullable();
            $table->string('location')->nullable();
            $table->json('properties')->nullable();
            $table->json('modified')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('url')->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->datetime('due_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->nullableMorphs('taskable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('key');
            $table->string('type')->nullable();
            $table->string('label_type')->nullable();
            $table->string('default')->nullable();
            $table->boolean('system')->default(false);
            $table->unsignedBigInteger('field_group_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'field_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('model');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'field_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('field_id');
            $table->string('model');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'field_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('field_id');
            $table->string('value');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'field_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('field_id');
            $table->morphs('field_valueable');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------------------
        // Commerce tables (customers, products, quotes, orders, invoices)
        // -------------------------------------------------------------------

        Schema::create($prefix.'customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('customerable_type')->nullable();
            $table->unsignedBigInteger('customerable_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'tax_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('rate');
            $table->boolean('default')->default(false);
            $table->string('tax_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'product_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->string('purchase_account')->nullable();
            $table->string('sales_account')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'product_variations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'product_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->integer('unit_price')->nullable();
            $table->integer('cost_per_unit')->nullable();
            $table->integer('direct_cost')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'quotes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('prefix')->nullable();
            $table->integer('number')->nullable();
            $table->string('reference')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('issue_at')->nullable();
            $table->datetime('expire_at')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();
            $table->text('terms')->nullable();
            $table->datetime('accepted_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'quote_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->integer('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('order_id')->nullable();
            $table->string('prefix')->nullable();
            $table->integer('number')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('quote_product_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->integer('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('invoice_id');
            $table->string('prefix')->nullable();
            $table->unsignedBigInteger('number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('sent')->default(false);
            $table->integer('amount_due')->nullable();
            $table->integer('amount_paid')->nullable();
            $table->datetime('fully_paid_at')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'invoice_lines', function (Blueprint $table) {
            // ...existing code...
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('description')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->integer('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------------------
        // Email & SMS marketing tables
        // -------------------------------------------------------------------

        Schema::create($prefix.'email_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'email_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('campaign_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('opens_count')->default(0);
            $table->unsignedInteger('unique_opens_count')->default(0);
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

        Schema::create($prefix.'email_campaign_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('email_campaign_id');
            $table->string('email');
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('opens_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->boolean('unsubscribed')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'sms_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('body')->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'sms_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('campaign_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('body')->nullable();
            $table->string('from')->nullable();
            $table->unsignedBigInteger('sms_template_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
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

        Schema::create($prefix.'sms_campaign_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('sms_campaign_id');
            $table->string('phone');
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->string('message_id')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->boolean('unsubscribed')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }
}
