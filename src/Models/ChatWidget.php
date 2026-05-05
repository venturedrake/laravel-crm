<?php

namespace VentureDrake\LaravelCrm\Models;

use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;
use VentureDrake\LaravelCrm\Traits\HasGlobalSettings;
use VentureDrake\LaravelCrm\Traits\SearchFilters;

class ChatWidget extends Model
{
    use BelongsToTeams;
    use HasGlobalSettings;
    use SearchFilters;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_origins' => 'array',
    ];

    protected $searchable = ['name'];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'chat_widgets';
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function visitors()
    {
        return $this->hasMany(ChatVisitor::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    /**
     * The JS embed snippet to copy onto an external website.
     */
    public function embedSnippet(): string
    {
        $url = url(route('laravel-crm.portal.chat.embed', ['publicKey' => $this->public_key]));

        return <<<JS
<!-- Laravel CRM Chat Widget -->
<script>(function(){var s=document.createElement('script');s.async=1;s.src='{$url}';document.head.appendChild(s);})();</script>
JS;
    }
}

