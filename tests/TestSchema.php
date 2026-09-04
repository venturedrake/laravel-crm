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
                $table->text('crm_permissions')->nullable();
                // Drives the stub's hasRole(); permissive when null.
                $table->text('crm_roles')->nullable();
                $table->timestamp('last_online_at')->nullable();
                // Added in production by add_mailing_list_to_users_table.php.stub
                $table->boolean('mailing_list')->default(true);
                $table->unsignedBigInteger('current_crm_team_id')->nullable();
                $table->unsignedBigInteger('current_team_id')->nullable();
                $table->text('team_ids')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Jetstream's tenancy pivot. Not a CRM migration, but UserController::store()
        // and UserIndex::users() both hard-require it when laravel-crm.teams is on.
        if (! Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->nullable();
                $table->timestamps();
            });
        }

        // The CRM's own team grouping (crm_teams / crm_team_user). Note these are
        // NOT prefixed by db_table_prefix in the production stub either.
        if (! Schema::hasTable('crm_teams')) {
            Schema::create('crm_teams', function (Blueprint $table) {
                $table->bigIncrements('id');
                // Added in production by add_team_id_to_laravel_crm_tables.php.stub;
                // the BelongsToTeams global scope on Team requires it.
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_owner_id')->nullable();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('crm_team_user')) {
            Schema::create('crm_team_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('crm_team_id');
                $table->unsignedBigInteger('user_id');
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
            $table->string('vat_number')->nullable();
            $table->string('website_url')->nullable();
            $table->string('linkedin')->nullable();
            $table->integer('number_of_employees')->nullable();
            $table->bigInteger('annual_revenue')->nullable();
            $table->bigInteger('total_money_raised')->nullable();
            $table->unsignedBigInteger('organization_type_id')->nullable();
            $table->unsignedBigInteger('timezone_id')->nullable();
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
            $table->text('description')->nullable();
            $table->unsignedBigInteger('pipeline_id');
            $table->unsignedBigInteger('pipeline_stage_probability_id')->nullable();
            $table->integer('order')->default(0);
            $table->string('color')->nullable();
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

        Schema::create($prefix.'deal_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->integer('price')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
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

        Schema::create($prefix.'industries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('offset')->nullable();
            $table->string('diff_from_gtm')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'organization_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'contact_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->morphs('contactable');
            $table->morphs('entityable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create($prefix.'calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->datetime('start_at')->nullable();
            $table->datetime('finish_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('reminder_email')->default(false);
            $table->boolean('reminder_sms')->default(false);
            $table->nullableMorphs('callable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'meetings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->datetime('start_at')->nullable();
            $table->datetime('finish_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('reminder_email')->default(false);
            $table->boolean('reminder_sms')->default(false);
            $table->nullableMorphs('meetingable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'lunches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->datetime('start_at')->nullable();
            $table->datetime('finish_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('reminder_email')->default(false);
            $table->boolean('reminder_sms')->default(false);
            $table->nullableMorphs('lunchable');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

        Schema::create($prefix.'files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->morphs('fileable');
            $table->string('file');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('format')->nullable();
            $table->string('filesize')->nullable();
            $table->string('mime')->nullable();
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->datetime('start_at')->nullable();
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
            // add_label_type_to_laravel_crm_fields_table drops `key` outright, so no
            // production schema still requires it. Kept nullable rather than removed so
            // any host still on the pre-drop schema keeps resolving.
            $table->string('key')->nullable();
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
            // The shipped table has no `model` column — a group is bound to its
            // models through crm_field_models. Kept nullable rather than dropped
            // so nothing that happened to write it starts failing.
            $table->string('model')->nullable();
            $table->string('handle')->nullable();
            $table->boolean('system')->default(false);
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
            $table->string('label')->nullable();
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

        // Core ships no migration stub for product_attributes, but the model, controller
        // and routes all exist. Mirrors the product_categories shape so the routes are
        // exercisable in tests.
        Schema::create($prefix.'product_attributes', function (Blueprint $table) {
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
            $table->string('pdf_template')->nullable();
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

        Schema::create($prefix.'delivery_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('delivery_id');
            // Present in production since the create stub; Order::deliveryComplete()
            // draws down against it.
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->integer('price')->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'address_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->string('delivery_id')->nullable();
            $table->string('prefix')->nullable();
            $table->integer('number')->nullable();
            $table->string('reference')->nullable();
            $table->string('pdf_template')->nullable();
            $table->datetime('delivery_initiated')->nullable();
            $table->datetime('delivery_shipped')->nullable();
            $table->datetime('delivery_expected')->nullable();
            $table->datetime('delivered_on')->nullable();
            $table->text('description')->nullable();
            $table->text('terms')->nullable();
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
            $table->decimal('quantity', 15, 3)->nullable();
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
            $table->string('pdf_template')->nullable();
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
            $table->decimal('quantity', 15, 3)->nullable();
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
            $table->string('pdf_template')->nullable();
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
            $table->decimal('quantity', 15, 3)->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'purchase_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('purchase_order_id')->nullable();
            $table->string('prefix')->nullable();
            $table->unsignedBigInteger('number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('adjustments')->nullable();
            $table->integer('total')->nullable();
            $table->string('delivery_type')->default('deliver');
            $table->string('pdf_template')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('sent')->default(false);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'purchase_order_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->text('description')->nullable();
            $table->text('comments')->nullable();
            $table->integer('order')->nullable();
            $table->integer('price')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->decimal('tax_rate')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });

        // Mirrors create_laravel_crm_xero_invoices_table. Present purely so
        // the invoice blades' `$invoice->xeroInvoice->number ?? ...` lookups
        // resolve to null instead of throwing "no such table" — without it no
        // invoice PDF can be rendered under test at all, which is how the
        // themed-template suite ended up never exercising a real invoice
        // download.
        Schema::create($prefix.'xero_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('xero_type')->nullable();
            $table->string('xero_id')->nullable();
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->integer('subtotal')->nullable();
            $table->integer('total_tax')->nullable();
            $table->integer('total')->nullable();
            $table->string('status')->nullable();
            $table->integer('amount_due')->nullable();
            $table->integer('amount_paid')->nullable();
            $table->integer('amount_credited')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('line_amount_types')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->datetime('fully_paid_at')->nullable();
            $table->datetime('xero_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'xero_purchase_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('xero_type')->nullable();
            $table->string('xero_id')->nullable();
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->integer('subtotal')->nullable();
            $table->integer('total_tax')->nullable();
            $table->integer('total')->nullable();
            $table->string('status')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('line_amount_types')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->datetime('xero_updated_at')->nullable();
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
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('email_campaign_id');
            $table->unsignedBigInteger('email_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('address')->nullable();
            $table->string('tracking_token', 64)->nullable();
            $table->string('status')->default('pending');
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
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('sms_campaign_id');
            $table->unsignedBigInteger('phone_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('tracking_token', 64)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('clicksend_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'feature_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->tinyInteger('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'features', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('feature_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedBigInteger('feature_status_id')->nullable();
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'feature_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('feature_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body');
            $table->boolean('is_admin_reply')->default(false);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'feature_votes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('feature_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['feature_id', 'user_id']);
        });

        Schema::create($prefix.'feature_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('feature_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('viewed_at');
        });

        Schema::create($prefix.'user_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('code', 64)->unique();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('email');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'monitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('monitor_id')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('https');
            $table->string('url', 1024);
            $table->string('host')->nullable();
            $table->string('method', 16)->default('GET');
            $table->json('headers')->nullable();
            $table->text('body')->nullable();
            $table->unsignedInteger('expected_status_code')->default(200);
            $table->unsignedInteger('interval')->default(5);
            $table->unsignedInteger('timeout')->default(30);
            $table->boolean('is_active')->default(true);
            $table->boolean('uptime_enabled')->default(true);
            $table->boolean('ssl_enabled')->default(false);
            $table->unsignedInteger('perf_threshold_ms')->nullable();
            $table->unsignedInteger('downtime_minutes_before_alert')->nullable();
            $table->string('last_status')->nullable();
            $table->unsignedInteger('last_response_time')->nullable();
            $table->unsignedInteger('last_status_code')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamp('down_since_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('perf_notified_at')->nullable();
            $table->timestamp('recovered_notified_at')->nullable();
            $table->timestamp('ssl_last_checked_at')->nullable();
            $table->string('ssl_status')->nullable();
            $table->string('ssl_issuer')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('ssl_notified_at')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->unsignedBigInteger('user_restored_id')->nullable();
            $table->unsignedBigInteger('user_owner_id')->nullable();
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'monitor_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('monitor_id');
            $table->string('type')->default('http');
            $table->string('status');
            $table->unsignedInteger('response_time')->nullable();
            $table->unsignedInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        // Chat tables. Mirrors create_laravel_crm_chat_tables.php.stub, with the two
        // follow-up patch stubs folded in (chat_conversations.lead_id and
        // chat_messages.visitor_read_at). FK constraints omitted per this file's convention.
        Schema::create($prefix.'chat_widgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('public_key', 64)->unique();
            $table->string('name');
            $table->string('welcome_message')->nullable();
            $table->string('color', 16)->default('#2563eb');
            $table->string('position', 16)->default('bottom-right');
            $table->json('allowed_origins')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'chat_visitors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('chat_widget_id');
            $table->string('visitor_token', 64)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('current_url', 1024)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'chat_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->string('chat_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('chat_widget_id');
            $table->unsignedBigInteger('chat_visitor_id');
            $table->string('subject')->nullable();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('user_assigned_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('user_deleted_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($prefix.'chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('chat_conversation_id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('visitor_read_at')->nullable();
            $table->timestamps();
        });
    }
}
