<?php

namespace VentureDrake\LaravelCrm\Models;

class SmsCampaignClick extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'sms_campaign_clicks';
    }

    public function recipient()
    {
        return $this->belongsTo(SmsCampaignRecipient::class, 'sms_campaign_recipient_id');
    }
}
