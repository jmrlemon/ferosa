<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'price',
        'stock_qty',
        'category',
        'is_active',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function inStock(): bool
    {
        return $this->stock_qty > 0;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
