<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Larastan reads `$casts` but not the `casts()` method this model uses, so the
 * datetime attributes below look like plain strings to static analysis. Declare
 * the types the cast actually produces.
 *
 * @property Carbon $appointment_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $archived_at
 */
class Appointment extends Model
{
    use Concerns\HasPayments;

    public const STATUS_TRANSITIONS = [
        'scheduled' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id',
        'service_type_id',
        'appointment_at',
        'slot_key',
        'status',
        'payment_status',
        'appointment_amount',
        'notes',
        'cancel_reason',
        'cancelled_at',
        'cancelled_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'appointment_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public static function slotKey(int $serviceTypeId, mixed $appointmentAt): string
    {
        return $serviceTypeId.'|'.Carbon::parse($appointmentAt)->format('Y-m-d H:i:00');
    }

    public function canTransitionTo(string $status): bool
    {
        return $status === $this->status
            || in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected function invoiceSeriesLetter(): string
    {
        return 'S';
    }
}
