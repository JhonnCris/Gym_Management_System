<?php

namespace Tests\Feature;

use App\Models\EquipmentMaintenanceLog;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Tests\TestCase;

class ExplicitPrimaryKeyNamingTest extends TestCase
{
    public function test_membership_plan_and_maintenance_log_use_explicit_primary_keys(): void
    {
        $this->assertSame('mem_plan_id', (new MembershipPlan())->getKeyName());
        $this->assertSame('eml_id', (new EquipmentMaintenanceLog())->getKeyName());
    }

    public function test_membership_plan_relationships_point_to_mem_plan_id(): void
    {
        $memberRelation = (new Member())->membershipPlan();
        $paymentRelation = (new Payment())->requestedMembershipPlan();
        $membersRelation = (new MembershipPlan())->members();
        $paymentsRelation = (new MembershipPlan())->requestedPayments();

        $this->assertSame('mem_plan_id', $memberRelation->getOwnerKeyName());
        $this->assertSame('mem_plan_id', $paymentRelation->getOwnerKeyName());
        $this->assertSame('mem_plan_id', $membersRelation->getLocalKeyName());
        $this->assertSame('mem_plan_id', $paymentsRelation->getLocalKeyName());
    }
}
