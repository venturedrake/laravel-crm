<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedLaravelCrmEmailTemplates extends Migration
{
    /**
     * Stable UUIDs for the seeded system templates so re-running this migration
     * (or rolling it back) is idempotent regardless of admins renaming templates.
     */
    private const TEMPLATES = [
        '6f8b1d4a-1c2e-4f3a-9a01-000000000001' => [
            'name' => 'Newsletter',
            'subject' => 'Your monthly newsletter from {company_name}',
            'method' => 'newsletter',
        ],
        '6f8b1d4a-1c2e-4f3a-9a01-000000000002' => [
            'name' => 'Announcement',
            'subject' => 'Big news from {company_name}',
            'method' => 'announcement',
        ],
        '6f8b1d4a-1c2e-4f3a-9a01-000000000003' => [
            'name' => 'Promotion',
            'subject' => 'A special offer just for you',
            'method' => 'promotion',
        ],
        '6f8b1d4a-1c2e-4f3a-9a01-000000000004' => [
            'name' => 'Welcome',
            'subject' => 'Welcome to {company_name}, {first_name}',
            'method' => 'welcome',
        ],
        '6f8b1d4a-1c2e-4f3a-9a01-000000000005' => [
            'name' => 'Re-engagement',
            'subject' => 'We miss you, {first_name}',
            'method' => 'reengagement',
        ],
    ];

    public function up()
    {
        $table = config('laravel-crm.db_table_prefix').'email_templates';

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
                'subject' => $template['subject'],
                'body' => $this->{$template['method']}(),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table(config('laravel-crm.db_table_prefix').'email_templates')
            ->whereIn('external_id', array_keys(self::TEMPLATES))
            ->delete();
    }

    private function newsletter(): string
    {
        return <<<HTML
<h1 style="margin:0 0 16px 0;font-size:24px;color:#111827;">Newsletter</h1>
<p>Hi {first_name},</p>
<p>Here is a quick recap of what's been happening this month.</p>
<h3 style="color:#111827;margin-top:24px;">Highlights</h3>
<ul>
  <li>Highlight one — short description.</li>
  <li>Highlight two — short description.</li>
  <li>Highlight three — short description.</li>
</ul>
<p style="margin-top:24px;"><a href="#" style="background:#2563eb;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Read more — [add your link here]</a></p>
<p style="margin-top:32px;color:#6b7280;font-size:13px;">Thanks for reading.</p>
HTML;
    }

    private function announcement(): string
    {
        return <<<HTML
<h1 style="margin:0 0 16px 0;font-size:24px;color:#111827;">We have news</h1>
<p>Hi {first_name},</p>
<p>We have something exciting to share — a quick announcement we wanted you to hear first.</p>
<p>Replace this paragraph with the details of your announcement. Keep it short, clear, and link to anywhere your readers can learn more.</p>
<p style="margin-top:24px;"><a href="#" style="background:#111827;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Learn more — [add your link here]</a></p>
HTML;
    }

    private function promotion(): string
    {
        return <<<HTML
<h1 style="margin:0 0 16px 0;font-size:24px;color:#111827;">A special offer</h1>
<p>Hi {first_name},</p>
<p style="font-size:18px;color:#111827;"><strong>Save 20% this week only.</strong></p>
<p>For a limited time, enjoy a special discount as a thank-you for being part of our community.</p>
<p style="margin-top:24px;"><a href="#" style="background:#dc2626;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600;">Shop the sale — [add your link here]</a></p>
<p style="margin-top:24px;color:#6b7280;font-size:13px;">Offer ends Sunday.</p>
HTML;
    }

    private function welcome(): string
    {
        return <<<HTML
<h1 style="margin:0 0 16px 0;font-size:24px;color:#111827;">Welcome</h1>
<p>Hi {first_name},</p>
<p>Welcome aboard — we're glad you're here.</p>
<h3 style="color:#111827;margin-top:24px;">Getting started</h3>
<ol>
  <li>Step one — set up your profile.</li>
  <li>Step two — explore the basics.</li>
  <li>Step three — reach out if you need a hand.</li>
</ol>
<p style="margin-top:24px;"><a href="#" style="background:#2563eb;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Get started — [add your link here]</a></p>
HTML;
    }

    private function reengagement(): string
    {
        return <<<HTML
<h1 style="margin:0 0 16px 0;font-size:24px;color:#111827;">We miss you</h1>
<p>Hi {first_name},</p>
<p>It has been a while — we wanted to check back in.</p>
<p>Here's a small token of appreciation for sticking with us. Click below to come back and see what's new.</p>
<p style="margin-top:24px;"><a href="#" style="background:#7c3aed;color:#ffffff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Come back — [add your link here]</a></p>
HTML;
    }
}
