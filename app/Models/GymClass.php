<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class GymClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $primaryKey = 'class_id';

    protected $fillable = [
        'class_name',
        'schedule_time',
        'max_slots',
    ];

    protected $casts = [
        'schedule_time' => 'datetime',
    ];

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(
            Staff::class,
            'class_trainer',
            'class_id',
            'staff_id'
        )->withTimestamps();
    }

    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'class_equipment',
            'class_id',
            'equipment_id'
        )->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'class_id', 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id', 'class_id');
    }

    /**
     * Get classes with bookings view (replaces classes_with_bookings_view)
     */
    public function scopeWithBookings(Builder $query)
    {
        return $query->leftJoin('bookings', 'classes.class_id', '=', 'bookings.class_id')
            ->leftJoin('class_trainer', 'classes.class_id', '=', 'class_trainer.class_id')
            ->leftJoin('staff', 'class_trainer.staff_id', '=', 'staff.staff_id')
            ->leftJoin('users', 'staff.user_id', '=', 'users.id')
            ->select([
                'classes.class_id',
                'classes.class_name',
                'classes.schedule_time',
                'classes.max_slots',
            ])
            ->selectRaw('COUNT(DISTINCT bookings.booking_id) as bookings_count')
            ->selectRaw('COUNT(DISTINCT class_trainer.staff_id) as trainer_count')
            ->selectRaw("GROUP_CONCAT(DISTINCT users.full_name ORDER BY users.full_name SEPARATOR ', ') as trainer_names")
            ->groupBy([
                'classes.class_id',
                'classes.class_name',
                'classes.schedule_time',
                'classes.max_slots',
            ]);
    }
}
