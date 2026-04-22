<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipments';

    protected $primaryKey = 'equipment_id';

    protected $fillable = [
        'name',
        'quantity',
        'status',
        'condition_status',
        'last_maintenance_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'last_maintenance_date' => 'date',
        ];
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            GymClass::class,
            'class_equipments',
            'equipment_id',
            'class_id'
        )->withTimestamps();
    }
}
