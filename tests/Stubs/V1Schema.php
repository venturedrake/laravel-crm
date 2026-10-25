<?php

namespace VentureDrake\LaravelCrm\Tests\Stubs;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Tests\TestSchema;

/**
 * A v1-shaped database (CRM 1.4.x) for testing the laravelcrm:v2 upgrade.
 *
 * Built from the v2 TestSchema and turned back into v1, so the nested
 * `laravelcrm:update` that laravelcrm:v2 runs has to succeed for real:
 *
 *   1. TestSchema::up(), which already carries the v1 morph shapes
 *      ({prefix}labelable_*, the activities morphs, field_models.model).
 *   2. The Spatie permission tables and the v1 `audits` table.
 *   3. The schema that only v2 creates is stripped (chat, email and SMS
 *      campaigns, features, monitors, user invitations), so those
 *      migrations run during the upgrade, as they do on a real v1 host.
 *   4. The v2 names are renamed back to v1: organisations,
 *      organisation_types and clients, and the organisation_id,
 *      organisation_type_id, client_id and clientable_* columns.
 *   5. The migrations table records every stub CRM 1.4.1 published (under
 *      the name the v2 provider would publish it) plus the v1-only names, as
 *      a v1 host's does.
 *
 * Gaps the seeders or database/updates hit belong here, not in TestSchema.
 */
class V1Schema
{
    /**
     * Published v2 stubs (by stub name) that CRM 1.4.1 did not ship. They
     * have no row in a v1 host's migrations table, so they run during the
     * upgrade. The first eight are the v2 names of renamed v1 stubs.
     *
     * @var list<string>
     */
    public const NOT_IN_V1 = [
        'create_laravel_crm_organization_types_table',
        'create_laravel_crm_customers_table',
        'add_name_to_laravel_crm_customers_table',
        'add_customer_to_laravel_crm_leads_table',
        'add_customer_to_laravel_crm_orders_table',
        'add_customer_to_laravel_crm_quotes_table',
        'add_customer_to_laravel_crm_deals_table',
        'add_extra_fields_to_laravel_crm_organizations_table',
        'add_lead_id_to_laravel_crm_chat_conversations_table',
        'add_mailing_list_to_users_table',
        'add_subscribed_to_laravel_crm_emails_table',
        'add_subscribed_to_laravel_crm_phones_table',
        'add_views_count_to_laravel_crm_features_table',
        'add_visitor_read_at_to_laravel_crm_chat_messages_table',
        'create_laravel_crm_chat_tables',
        'create_laravel_crm_chat_visitor_page_views_table',
        'create_laravel_crm_email_campaign_clicks_table',
        'create_laravel_crm_email_campaign_recipients_table',
        'create_laravel_crm_email_campaigns_table',
        'create_laravel_crm_email_templates_table',
        'create_laravel_crm_feature_comments_table',
        'create_laravel_crm_feature_statuses_table',
        'create_laravel_crm_feature_views_table',
        'create_laravel_crm_feature_votes_table',
        'create_laravel_crm_features_table',
        'create_laravel_crm_monitor_checks_table',
        'create_laravel_crm_monitors_table',
        'create_laravel_crm_sms_campaign_clicks_table',
        'create_laravel_crm_sms_campaign_recipients_table',
        'create_laravel_crm_sms_campaigns_table',
        'create_laravel_crm_sms_templates_table',
        'ensure_encryptable_columns_widened_on_laravel_crm_tables',
        'fix_line1_nullable_on_laravel_crm_addresses_table',
        'make_name_nullable_on_laravel_crm_monitors_table',
        'seed_laravel_crm_email_templates',
        'seed_laravel_crm_sms_templates',
    ];

    /**
     * Migrations a v1 host ran that have no v2 file under the same name,
     * with host-like timestamps.
     *
     * @var list<string>
     */
    public const V1_ONLY_MIGRATIONS = [
        '2021_03_02_093511_create_laravel_crm_organisation_types_table',
        '2021_03_02_093512_add_teams_fields',
        '2021_03_02_093513_create_audits_table',
        '2021_03_02_093514_create_logins_table',
        '2021_03_02_093515_update_logins_and_devices_table_user_relation',
        '2022_06_14_101201_create_laravel_crm_clients_table',
        '2022_06_14_101202_add_name_to_laravel_crm_clients_table',
        '2022_06_14_101203_add_client_to_laravel_crm_leads_table',
        '2022_06_14_101204_add_client_to_laravel_crm_deals_table',
        '2022_06_14_101205_add_client_to_laravel_crm_orders_table',
        '2022_06_14_101206_add_client_to_laravel_crm_quotes_table',
        self::V1_EXTRA_FIELDS_MIGRATION,
    ];

    public const V1_EXTRA_FIELDS_MIGRATION = '2024_02_27_111124_add_extra_fields_to_laravel_crm_organisations_table';

    public static function up(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');

        TestSchema::up();

        // ---------- Spatie permissions + the CRM's extra role/permission columns ----------
        (require __DIR__.'/../../database/migrations/create_permission_tables.php.stub')->up();

        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->boolean('crm_role')->default(0);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->boolean('crm_permission')->default(0);
        });

        // ---------- v1 audits (owen-it/laravel-auditing, unprefixed) ----------
        Schema::create('audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->morphs('auditable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });

        // ---------- v1 tables TestSchema leaves out, read by the seeders ----------
        Schema::create($prefix.'pipeline_stage_probabilities', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->unsignedBigInteger('team_id')->index()->nullable();
            $table->string('name');
            $table->unsignedTinyInteger('percent');
            $table->tinyInteger('limit')->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------- strip the schema only v2 creates ----------
        foreach ([
            'chat_messages', 'chat_conversations', 'chat_visitors', 'chat_widgets',
            'email_campaign_recipients', 'email_campaigns', 'email_templates',
            'sms_campaign_recipients', 'sms_campaigns', 'sms_templates',
            'feature_views', 'feature_votes', 'feature_comments', 'features', 'feature_statuses',
            'monitor_checks', 'monitors', 'user_invitations',
        ] as $table) {
            Schema::drop($prefix.$table);
        }

        // ---------- v1 names ----------
        Schema::rename($prefix.'organizations', $prefix.'organisations');
        Schema::rename($prefix.'organization_types', $prefix.'organisation_types');
        Schema::rename($prefix.'customers', $prefix.'clients');

        // The v1 extra-fields migration added 16 columns; TestSchema carries 7 of them.
        Schema::table($prefix.'organisations', function (Blueprint $table) {
            $table->renameColumn('organization_type_id', 'organisation_type_id');
            $table->string('domain')->nullable();
            $table->smallInteger('year_founded')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('pinterest')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('google')->nullable();
        });

        foreach (['people', 'leads', 'deals', 'quotes', 'orders', 'invoices', 'purchase_orders', 'xero_contacts'] as $table) {
            Schema::table($prefix.$table, function (Blueprint $table) {
                $table->renameColumn('organization_id', 'organisation_id');
            });
        }

        // TestSchema keeps both client_id and customer_id; v1 had only client_id.
        foreach (['leads', 'deals', 'quotes', 'orders'] as $table) {
            Schema::table($prefix.$table, function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        Schema::table($prefix.'clients', function (Blueprint $table) {
            $table->renameColumn('customerable_type', 'clientable_type');
            $table->renameColumn('customerable_id', 'clientable_id');
        });

        // ---------- migrations table ----------
        $repository = app('migration.repository');
        $repository->createRepository();

        foreach (ServiceProvider::pathsToPublish(LaravelCrmServiceProvider::class, 'migrations') as $stub => $published) {
            if (! in_array(basename($stub, '.php.stub'), self::NOT_IN_V1, true)) {
                $repository->log(basename($published, '.php'), 1);
            }
        }

        foreach (self::V1_ONLY_MIGRATIONS as $migration) {
            $repository->log($migration, 1);
        }
    }
}
