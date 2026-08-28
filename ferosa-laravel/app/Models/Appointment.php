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

    /**
     * A confirmed appointment can go back to scheduled so a customer can move
     * a visit the team already confirmed: the new time has not been agreed with
     * anyone yet, so it drops back to awaiting confirmation rather than keeping
     * a confirmation that referred to a different day.
     *
     * @var array<string, list<string>>
     */
    public const STATUS_TRANSITIONS = [
        'scheduled' => ['confirmed', 'cancelled'],
        'confirmed' => ['scheduled', 'completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * The visit times a crew can be dispatched to. The booking form renders its
     * buttons from this list and StoreScheduleRequest validates against it, so
     * the two cannot drift: the times used to live only in the Blade template,
     * which meant a posted 3 a.m. appointment was accepted without complaint.
     */
    public const SLOT_TIMES = ['09:00', '10:30', '13:00', '14:30', '16:00'];

    protected $fillable = [
        'user_id',
        'service_type_id',
        'appointment_at',
        'slot_key',
        'status',
        'payment_status',
        'appointment_amount',
        'notes',
        'scope_notes',
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ServiceType, $this> */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasOne<Feedback, $this> */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public static function slotKey(int $serviceTypeId, mixed $appointmentAt): string
    {
        return $serviceTypeId.'|'.Carbon::parse($appointmentAt)->format('Y-m-d H:i:00');
    }

    /**
     * A crew is dispatched to a booked visit, so a customer cannot move or
     * cancel one at the last minute. The window is the same 24 hours a booking
     * has to be made in advance (StoreScheduleRequest), so there is one rule to
     * hold in your head rather than two.
     */
    public const CHANGE_NOTICE_HOURS = 24;

    /**
     * Whether the customer may still move or cancel this visit themselves.
     * Staff are not bound by this - they are the ones the notice protects.
     */
    public function isCustomerChangeable(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed'], true)
            && $this->appointment_at->greaterThanOrEqualTo(
                Carbon::now()->addHours(self::CHANGE_NOTICE_HOURS)
            );
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
