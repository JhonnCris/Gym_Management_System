<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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
