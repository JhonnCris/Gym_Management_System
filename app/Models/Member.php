<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $primaryKey = 'member_id';

    protected $fillable = [
        'user_id',
        'phone',
        'membership_type',
        'join_date',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'member_id', 'member_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'member_id', 'member_id');
    }
}
