<?php

namespace VentureDrake\LaravelCrm\Models;

class EmailCampaignRecipient extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'email_campaign_recipients';
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function email()
    {
        return $this->belongsTo(Email::class, 'email_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function clicks()
    {
        return $this->hasMany(EmailCampaignClick::class, 'email_campaign_recipient_id');
    }
}
