<?php

namespace App\Models;

use Database\Factories\WorkingDayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkingDay extends Model
{
    /** @use HasFactory<WorkingDayFactory> */
    use HasFactory;

    public const DAYS_OF_WEEK = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'day_of_week',
        'is_open',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include open business days.
     *
     * @param  Builder<WorkingDay>  $query
     * @return Builder<WorkingDay>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true);
    }

    /**
     * Get time slots configured for this day of week.
     *
     * @return HasMany<TimeSlot, $this>
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'day_of_week', 'day_of_week');
    }

    /**
     * Get human-readable label for the day.
     */
    public function getLabelAttribute(): string
    {
        return ucfirst($this->day_of_week);
    }
}
