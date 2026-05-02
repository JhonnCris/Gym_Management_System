<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipments';

    protected $primaryKey = 'equipment_id';

    protected $fillable = [
        'name',
        'quantity',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            GymClass::class,
            'class_equipment',
            'equipment_id',
            'class_id'
        )->withTimestamps();
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceLog::class, 'equipment_id', 'equipment_id')->latest();
    }
}
