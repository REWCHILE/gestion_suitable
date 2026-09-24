<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $table = 'campaigns';
    public $timestamps = false; // Uses created_at

    protected $fillable = [
        'name',
        'group_id',
        'template_id',
        'subject',
        'preheader',
        'ai_provider',
        'ai_prompt',
        'status',
        'sent_count',
        'total_count',
        'user_id'
    ];

    protected $casts = [
        'template_id' => 'integer',
        'sent_count' => 'integer',
        'total_count' => 'integer',
        'created_at' => 'datetime'
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }
}
