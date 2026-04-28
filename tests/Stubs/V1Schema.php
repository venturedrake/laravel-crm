<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal v1-shaped schema for testing the laravelcrm:v2 upgrade command.
 *
 * Mirrors only the tables/columns that LaravelCrmV2 touches:
 *   - tables renamed:        organisations, organisation_types, clients
 *   - columns renamed:       organisation_id, organisation_type_id, client_id, clientable_*
 *   - polymorphic *_type:    emails, phones, addresses, field_values, notes,
 *                            contacts (contactable_type + entityable_type),
 *                            files, customers (after rename), audits (auditable + user)
 *
 * NOTE: This is a hand-built minimal v1 schema, not the verbatim v1 migrations.
 * It is sufficient to exercise every branch of LaravelCrmV2::handle().
 */
class V1Schema
{
    public static function up(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');

        // ---------- Spatie permissions (standard, unprefixed) ----------
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        // ---------- v1: organisations / organisation_types ----------
        Schema::create($prefix.'organisation_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create($prefix.'organisations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('organisation_type_id')->nullable();
            $table->unsignedBigInteger('user_created_id')->nullable();
            $table->unsignedBigInteger('user_updated_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------- v1: people (FK to organisations) ----------
        Schema::create($prefix.'people', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------- v1: clients (polymorphic clientable) ----------
        Schema::create($prefix.'clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id')->nullable();
            $table->string('name')->nullable();
            $table->string('clientable_type')->nullable();
            $table->unsignedBigInteger('clientable_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------- v1: entity tables (client_id + organisation_id) ----------
        foreach (['leads', 'deals', 'quotes', 'orders'] as $entity) {
            Schema::create($prefix.$entity, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('title')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('organisation_id')->nullable();
                $table->unsignedBigInteger('person_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ---------- v1: organisation_id only ----------
        foreach (['invoices', 'purchase_orders', 'xero_contacts'] as $entity) {
            Schema::create($prefix.$entity, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('organisation_id')->nullable();
                $table->timestamps();
            });
        }

        // ---------- v1: polymorphic relation tables ----------
        Schema::create($prefix.'emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('address');
            $table->morphs('emailable');
            $table->timestamps();
        });

        Schema::create($prefix.'phones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('number');
            $table->morphs('phoneable');
            $table->timestamps();
        });

        Schema::create($prefix.'addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('line1')->nullable();
            $table->morphs('addressable');
            $table->timestamps();
        });

        Schema::create($prefix.'field_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('field_id');
            $table->morphs('field_valueable');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content');
            $table->morphs('noteable');
            $table->timestamps();
        });

        Schema::create($prefix.'contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('contactable_type')->nullable();
            $table->unsignedBigInteger('contactable_id')->nullable();
            $table->string('entityable_type')->nullable();
            $table->unsignedBigInteger('entityable_id')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('filename');
            $table->morphs('fileable');
            $table->timestamps();
        });

        // ---------- audits (unprefixed, owen-it/laravel-auditing) ----------
        Schema::create('audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->morphs('auditable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->timestamps();
        });
    }
}
