<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class EmailCampaign extends Model
{
    use BelongsToTeams;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $searchable = [
        'campaign_id',
        'name',
        'subject',
    ];

    protected $filterable = [
        'status',
        'user_owner_id',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'email_campaigns';
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function recipients()
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancellable(): bool
    {
        return $this->status === 'scheduled';
    }

    public function openRate(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->unique_opens_count / $this->total_recipients) * 100, 1);
    }

    public function clickRate(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->unique_clicks_count / $this->total_recipients) * 100, 1);
    }

    public function unsubscribeRate(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->unsubscribes_count / $this->total_recipients) * 100, 1);
    }
}
