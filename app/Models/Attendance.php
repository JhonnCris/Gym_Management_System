<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
