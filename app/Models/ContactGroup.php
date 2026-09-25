<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactGroup extends Model
{
    protected $table = 'contact_groups';
    public $timestamps = false; // Has created_at

    protected $fillable = [
        'name',
        'description',
        'color'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'group_members', 'group_id', 'client_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'group_id');
    }
}
