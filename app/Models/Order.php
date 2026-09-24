<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';
    public $timestamps = false;

    protected $fillable = [
        'wc_order_id',
        'customer_name',
        'customer_email',
        'customer_city',
        'total_amount',
        'shipping_amount',
        'status',
        'payment_method',
        'items_count',
        'date_created'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'items_count' => 'integer',
        'date_created' => 'datetime'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
