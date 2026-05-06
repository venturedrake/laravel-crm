<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedLaravelCrmSmsTemplates extends Migration
{
    /**
     * Stable UUIDs for the seeded system templates so re-running this migration
     * (or rolling it back) is idempotent regardless of admins renaming templates.
     */
    private const TEMPLATES = [
        '7a9b2e5b-2d3f-5a4b-8b12-000000000001' => [
            'name' => 'Promotion',
            'method' => 'promotion',
        ],
        '7a9b2e5b-2d3f-5a4b-8b12-000000000002' => [
            'name' => 'Reminder',
            'method' => 'reminder',
        ],
        '7a9b2e5b-2d3f-5a4b-8b12-000000000003' => [
            'name' => 'Welcome',
            'method' => 'welcome',
        ],
    ];

    public function up()
    {
        $table = config('laravel-crm.db_table_prefix').'sms_templates';

        $now = now();

        foreach (self::TEMPLATES as $externalId => $template) {
            $exists = DB::table($table)
                ->where('external_id', $externalId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table($table)->insert([
                'external_id' => $externalId,
                'name' => $template['name'],
                'body' => $this->{$template['method']}(),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table(config('laravel-crm.db_table_prefix').'sms_templates')
            ->whereIn('external_id', array_keys(self::TEMPLATES))
            ->delete();
    }

    private function promotion(): string
    {
        return "Hi {first_name}, save 20% this week only at {company_name}. Shop now: [add your link here]";
    }

    private function reminder(): string
    {
        return "Hi {first_name}, this is a friendly reminder from {company_name}. [add your details here]";
    }

    private function welcome(): string
    {
        return "Welcome to {company_name}, {first_name}! Thanks for joining us. Reply STOP to opt out.";
    }
}
