<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrainConversation extends Model
{
    protected $table = 'brain_conversations';

    protected $fillable = [
        'title',
        'provider',
        'model',
        'summary',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(BrainMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }
}
