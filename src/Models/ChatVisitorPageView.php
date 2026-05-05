<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use VentureDrake\LaravelCrm\Traits\BelongsToTeams;

/**
 * A page view recorded from the chat widget loader.
 * Lightweight (no audit, no soft deletes) — high write volume.
 */
class ChatVisitorPageView extends EloquentModel
{
    use BelongsToTeams;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'chat_visitor_page_views';
    }

    public function visitor()
    {
        return $this->belongsTo(ChatVisitor::class, 'chat_visitor_id');
    }

    public function host(): ?string
    {
        return parse_url($this->url, PHP_URL_HOST) ?: null;
    }

    public function path(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH) ?: '/';
        $query = parse_url($this->url, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
    }
}
