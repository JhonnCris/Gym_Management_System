<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'member_id',
        'class_id',
        'check_in_time',
        'check_out_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class, 'class_id', 'class_id');
    }

    public function scopeWithDetails(Builder $query)
    {
        return $query
            ->join('members', 'attendances.member_id', '=', 'members.member_id')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->join('classes', 'attendances.class_id', '=', 'classes.class_id')
            ->select([
                'attendances.attendance_id',
                'attendances.member_id',
                'users.full_name as member_name',
                'users.email as member_email',
                'attendances.class_id',
                'classes.class_name',
                'attendances.check_in_time',
                'attendances.check_out_time',
                'attendances.status as attendance_status',
            ]);
    }

    public static function forDateWithDetails(string $date): Builder
    {
        return self::query()
            ->withDetails()
            ->whereDate('attendances.check_in_time', $date)
            ->orderByDesc('attendances.check_in_time');
    }

    public static function forMemberWithDetails(int $memberId): Builder
    {
        return self::query()
            ->withDetails()
            ->where('attendances.member_id', $memberId)
            ->orderByDesc('attendances.check_in_time');
    }
}
