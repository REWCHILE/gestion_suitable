<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $table = 'email_logs';
    public $timestamps = false; // Uses sent_at

    protected $fillable = [
        'client_id',
        'campaign_id',
        'user_id',
        'template_id',
        'recipient_email',
        'subject',
        'status',
        'details',
        'sent_at'
    ];

    protected $casts = [
        'template_id' => 'integer',
        'sent_at' => 'datetime'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
