<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id',
        'order_number',
        'checkout_token',
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
        'payment_reference_normalized',
        'payment_proof_path',
        'payment_review_notes',
        'payment_verified_at',
        'payment_verified_by',
        'delivery_proof_url',
        'dispatch_proof_url',
        'dispatched_at',
        'driver_name',
        'driver_phone',
        'dispatch_notes',
        'delivery_recipient_name',
        'delivered_at',
        'customer_confirmed_at',
        'cancel_reason',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $hidden = [
        'payment_reference_normalized',
        'payment_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'archived_at' => 'datetime',
            'delivered_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'customer_confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_verified_at' => 'datetime',
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

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public static function normalizePaymentReference(?string $reference): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $reference));

        return $normalized !== '' ? $normalized : null;
    }

    public function canTransitionTo(string $status): bool
    {
        return $status === $this->status
            || in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }
}
