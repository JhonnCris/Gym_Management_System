<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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

    protected $casts = [];

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

    /**
     * Get equipment with classes view (replaces equipment_with_classes_view)
     */
    public function scopeWithClasses(Builder $query)
    {
        return $query->leftJoin('class_equipment', 'equipments.equipment_id', '=', 'class_equipment.equipment_id')
            ->select([
                'equipments.equipment_id',
                'equipments.name',
                'equipments.quantity',
                'equipments.status',
                'equipments.condition_status',
                'equipments.last_maintenance_date',
                'equipments.description',
            ])
            ->selectRaw('COUNT(DISTINCT class_equipment.class_id) as classes_count')
            ->groupBy([
                'equipments.equipment_id',
                'equipments.name',
                'equipments.quantity',
                'equipments.status',
                'equipments.condition_status',
                'equipments.last_maintenance_date',
                'equipments.description',
            ]);
    }
}
