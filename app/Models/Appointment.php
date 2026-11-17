<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_reference',
        'customer_name',
        'customer_email',
        'customer_phone',
        'appointment_date',
        'time_slot_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'admin_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
        ];
    }

    /**
     * Bootstrap model events.
     */
    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            if (empty($appointment->booking_reference)) {
                $appointment->booking_reference = self::generateBookingReference();
            }
        });
    }

    /**
     * Generate a unique readable booking reference.
     */
    public static function generateBookingReference(): string
    {
        do {
            $ref = 'APT-'.strtoupper(Str::random(8));
        } while (self::where('booking_reference', $ref)->exists());

        return $ref;
    }

    /**
     * The time slot associated with this appointment.
     *
     * @return BelongsTo<TimeSlot, $this>
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * Scope query to upcoming appointments.
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('appointment_date', '>=', Carbon::today())
            ->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    /**
     * Scope query for today.
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('appointment_date', Carbon::today());
    }

    /**
     * Formatted date string (e.g., "Monday, November 2, 2026").
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->appointment_date ? $this->appointment_date->format('l, F j, Y') : '';
    }

    /**
     * Formatted time range string (e.g., "9:00 AM - 10:00 AM").
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');

        return "{$start} - {$end}";
    }

    /**
     * Color styling for status badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_PENDING => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_COMPLETED => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_CANCELLED => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
