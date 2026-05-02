<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'schedule_time' => 'datetime',
        ];
    }

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
}
