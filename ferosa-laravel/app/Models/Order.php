<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'total_amount',
        'items',
        'archived_at',
        'delivery_method',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_city',
        'delivery_notes',
        'payment_method',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function feedback(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Feedback::class);
    }
}
