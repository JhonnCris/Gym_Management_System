<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminEquipmentViewTest extends TestCase
{
    public function test_admin_equipment_view_renders_database_rows_without_relation_access(): void
    {
        $html = view('admin.equipment', [
            'equipmentItems' => collect([
                (object) [
                    'name' => 'Treadmill',
                    'description' => 'Cardio machine',
                    'quantity' => 3,
                    'status' => 'Available',
                    'last_maintenance_date' => Carbon::create(2026, 4, 20),
                ],
            ]),
            'stats' => [
                'tracked_items' => 1,
                'total_units' => 3,
                'available_items' => 1,
                'attention_items' => 0,
                'linked_to_classes' => 0,
            ],
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('Treadmill', $html);
        $this->assertStringContainsString('Apr 20, 2026', $html);
        $this->assertStringNotContainsString('maintenanceLogs', $html);
    }
}
