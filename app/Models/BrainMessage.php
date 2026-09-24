<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrainMessage extends Model
{
    protected $table = 'brain_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'provider',
        'model',
        'tokens_used',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BrainConversation::class, 'conversation_id');
    }
}
