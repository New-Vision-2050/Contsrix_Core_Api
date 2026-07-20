<?php

namespace Tests\Unit\Project;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Tests\TestCase;

class ProjectOrderPermitRelationsTest extends TestCase
{
    public function test_order_permit_has_department_relation(): void
    {
        $model = new OrderPermit();

        $this->assertTrue(method_exists($model, 'department'));
    }

    public function test_order_permit_department_has_order_permits_relation(): void
    {
        $model = new OrderPermitDepartment();

        $this->assertTrue(method_exists($model, 'orderPermits'));
    }

    public function test_project_management_has_order_permits_relation(): void
    {
        $model = new ProjectManagement();

        $this->assertTrue(method_exists($model, 'orderPermits'));
    }
}
