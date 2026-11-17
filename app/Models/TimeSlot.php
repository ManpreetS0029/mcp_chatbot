<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TimeSlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'capacity',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only active slots.
     *
     * @param  Builder<TimeSlot>  $query
     * @return Builder<TimeSlot>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Appointments booked for this slot.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Related working day configuration if recurring.
     *
     * @return BelongsTo<WorkingDay, $this>
     */
    public function workingDay(): BelongsTo
    {
        return $this->belongsTo(WorkingDay::class, 'day_of_week', 'day_of_week');
    }

    /**
     * Format start time to readable 12-hour format (e.g. 09:00 AM).
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    /**
     * Format end time to readable 12-hour format (e.g. 10:00 AM).
     */
    public function getFormattedEndTimeAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('g:i A');
    }

    /**
     * Formatted range (e.g. 9:00 AM - 10:00 AM).
     */
    public function getTimeRangeAttribute(): string
    {
        return "{$this->formatted_start_time} - {$this->formatted_end_time}";
    }

    /**
     * Calculate remaining capacity for a given date.
     */
    public function getRemainingCapacityForDate(string $date): int
    {
        $bookedCount = $this->appointments()
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return max(0, $this->capacity - $bookedCount);
    }

    /**
     * Check if slot has available capacity on a given date.
     */
    public function isAvailableForDate(string $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->getRemainingCapacityForDate($date) > 0;
    }
}
