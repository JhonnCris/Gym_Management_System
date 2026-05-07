<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $primaryKey = 'booking_id';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'class_id',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class, 'class_id', 'class_id');
    }

    /**
     * Get member bookings view (replaces member_bookings_view)
     */
    public function scopeWithMemberBookings(Builder $query)
    {
        return $query->join('classes', 'bookings.class_id', '=', 'classes.class_id')
            ->leftJoin('class_trainer', 'classes.class_id', '=', 'class_trainer.class_id')
            ->leftJoin('users', 'class_trainer.staff_id', '=', 'users.id')
            ->select([
                'bookings.booking_id',
                'bookings.member_id',
                'bookings.class_id',
                'bookings.status as booking_status',
                'classes.class_name',
                'classes.schedule_time',
                'classes.max_slots',
                'class_trainer.staff_id',
                'users.full_name as trainer_name',
            ]);
    }

    /**
     * Get member bookings (replaces get_member_bookings procedure)
     */
    public static function getMemberBookings(int $memberId)
    {
        return self::withMemberBookings()
            ->where('bookings.member_id', $memberId)
            ->orderBy('classes.schedule_time')
            ->get();
    }

    /**
     * Get member booking schedule (replaces get_member_booking_schedule procedure)
     */
    public static function getMemberBookingSchedule(int $memberId)
    {
        return self::join('classes', 'bookings.class_id', '=', 'classes.class_id')
            ->leftJoin('class_trainer', 'classes.class_id', '=', 'class_trainer.class_id')
            ->leftJoin('staff', 'class_trainer.staff_id', '=', 'staff.staff_id')
            ->leftJoin('users', 'staff.user_id', '=', 'users.id')
            ->where('bookings.member_id', $memberId)
            ->select([
                'bookings.booking_id',
                'bookings.member_id',
                'bookings.class_id',
                'bookings.status as booking_status',
                'classes.class_name',
                'classes.schedule_time',
                'classes.max_slots',
                'classes.class_id as class_id_ref',
            ])
            ->selectRaw('COUNT(DISTINCT bookings.booking_id) as bookings_count')
            ->selectRaw('COUNT(DISTINCT class_trainer.staff_id) as trainer_count')
            ->selectRaw("GROUP_CONCAT(DISTINCT users.full_name ORDER BY users.full_name SEPARATOR ', ') as trainer_names")
            ->groupBy([
                'bookings.booking_id',
                'bookings.member_id',
                'bookings.class_id',
                'bookings.status',
                'classes.class_name',
                'classes.schedule_time',
                'classes.max_slots',
                'classes.class_id',
            ])
            ->orderBy('classes.schedule_time', 'desc')
            ->orderBy('bookings.booking_id', 'desc')
            ->get();
    }
}
