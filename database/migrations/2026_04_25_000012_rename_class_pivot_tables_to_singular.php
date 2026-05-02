<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_trainers') && ! Schema::hasTable('class_trainer')) {
            Schema::rename('class_trainers', 'class_trainer');
        }

        if (Schema::hasTable('class_equipments') && ! Schema::hasTable('class_equipment')) {
            Schema::rename('class_equipments', 'class_equipment');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_trainer') && ! Schema::hasTable('class_trainers')) {
            Schema::rename('class_trainer', 'class_trainers');
        }

        if (Schema::hasTable('class_equipment') && ! Schema::hasTable('class_equipments')) {
            Schema::rename('class_equipment', 'class_equipments');
        }
    }
};
