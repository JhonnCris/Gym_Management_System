<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenanceLog extends Model
{
    protected $table = 'equipment_maintenance_logs';

    protected $primaryKey = 'eml_id';

    protected $fillable = [
        'equipment_id',
        'maintenance_type',
        'status',
        'description',
        'performed_by',
        'performed_date',
        'next_scheduled_date',
    ];

    protected $casts = [
        'performed_date' => 'datetime',
        'next_scheduled_date' => 'datetime',
    ];

    /**
     * Get the equipment this maintenance log belongs to
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    /**
     * Get the staff member who performed the maintenance
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by', 'staff_id');
    }
}
