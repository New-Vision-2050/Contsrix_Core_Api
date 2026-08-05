<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Imports\ProjectOrderPermitUdsImport;
use Modules\Project\ProjectType\Jobs\ImportProjectOrderPermitUdsJob;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermitUds;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;
use Tests\TestCase;

final class ProjectOrderPermitUdsImportTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRow(string $name, string $typeCode, array $overrides = []): array
    {
        $row = array_fill(0, 40, null);
        $row[6] = 'موقف مقاول';
        $row[12] = '1500.50';
        $row[13] = 'رصيد مواد';
        $row[16] = 'سلة مشتركة';
        $row[24] = '2024-01-10';
        $row[25] = '2024-02-15';
        $row[27] = 'جهة تنفيذ';
        $row[28] = '2024-03-01';
        $row[30] = '155';
        $row[31] = '100';
        $row[32] = 'CNT-1';
        $row[33] = 'نوع';
        $row[34] = $name;
        $row[35] = $typeCode;
        $row[36] = 'مقاول الاختبار';
        $row[37] = 'توصيلات';

        foreach ($overrides as $index => $value) {
            $row[$index] = $value;
        }

        return $row;
    }

    public function test_mapper_skips_header_and_empty_name_rows(): void
    {
        $mapper = new ProjectOrderPermitUdsImport();

        $header = $this->makeRow('رقم أمر العمل', 'رمز النوع');
        $empty = $this->makeRow('', '401');
        $valid = $this->makeRow('WO-100', '401');

        $this->assertTrue($mapper->isHeaderRow($header));
        $this->assertNull($mapper->mapRow($header, 'p1', 'c1', (string) Str::uuid(), now()->toDateTimeString()));
        $this->assertNull($mapper->mapRow($empty, 'p1', 'c1', (string) Str::uuid(), now()->toDateTimeString()));

        $mapped = $mapper->mapRow($valid, 'p1', 'c1', 'id-1', '2026-08-05 10:00:00', true, false);

        $this->assertNotNull($mapped);
        $this->assertSame('WO-100', $mapped['name']);
        $this->assertSame('401', $mapped['type_code']);
        $this->assertSame('سلة مشتركة', $mapped['current_entity']);
        $this->assertSame('سلة مشتركة', $mapped['contractor_basket']);
        $this->assertNull($mapped['consultant_current_basket']);
        $this->assertSame('155', $mapped['contractor_last_procedure_code']);
        $this->assertNull($mapped['consultant_last_procedure_code']);
        $this->assertNull($mapped['price']);
        $this->assertNull($mapped['consultant_price']);
        $this->assertSame('100', $mapped['labor_cost']);
        $this->assertSame('CNT-1', $mapped['contract_number']);
        $this->assertNull($mapped['subscriber_type']);

        $consultantMapped = $mapper->mapRow(
            $this->makeRow('WO-100', '912'),
            'p1',
            'c1',
            'id-2',
            '2026-08-05 10:00:00',
            false,
            true,
        );

        $this->assertNotNull($consultantMapped);
        $this->assertNull($consultantMapped['contractor_basket']);
        $this->assertSame('سلة مشتركة', $consultantMapped['consultant_current_basket']);
        $this->assertSame(1500.5, $consultantMapped['price']);
        $this->assertSame(1500.5, $consultantMapped['consultant_price']);
        $this->assertSame('2024-02-15', $consultantMapped['assigned_date']);
        $this->assertNull($consultantMapped['contractor_last_procedure_code']);
        $this->assertSame('155', $consultantMapped['consultant_last_procedure_code']);
    }

    public function test_job_deletes_temp_file_on_excel_failure(): void
    {
        Storage::fake('public');

        $failPath = 'temp_imports/uds-fail.xlsx';
        Storage::disk('public')->put($failPath, 'fake');

        Excel::shouldReceive('toArray')
            ->once()
            ->andThrow(new \RuntimeException('corrupt excel'));

        $jobFail = new ImportProjectOrderPermitUdsJob($failPath, 'project-1', 'company-1');

        try {
            $jobFail->handle(app(ProjectOrderPermitService::class));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('corrupt excel', $e->getMessage());
        }

        Storage::disk('public')->assertMissing($failPath);
    }

    public function test_excel_read_failure_does_not_delete_existing_uds_rows(): void
    {
        if (!Schema::hasTable('project_order_permit_uds')) {
            $this->markTestSkipped('project_order_permit_uds table missing. Run migrations.');
        }

        $company = Company::withoutGlobalScopes()->first();
        $project = ProjectManagement::withoutGlobalScopes()->first();

        if (!$company || !$project) {
            $this->markTestSkipped('Need at least one company and project in DB.');
        }

        Storage::fake('public');
        $path = 'temp_imports/uds-keep.xlsx';
        Storage::disk('public')->put($path, 'fake');

        $existingId = (string) Str::uuid();
        ProjectOrderPermitUds::withoutGlobalScopes()->insert([
            'id' => $existingId,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'name' => 'KEEP-ME',
            'type_code' => '401',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Excel::shouldReceive('toArray')
            ->once()
            ->andThrow(new \RuntimeException('cannot parse'));

        $job = new ImportProjectOrderPermitUdsJob($path, (string) $project->id, (string) $company->id);

        try {
            $job->handle(app(ProjectOrderPermitService::class));
            $this->fail('Expected exception');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertTrue(
            ProjectOrderPermitUds::withoutGlobalScopes()->where('id', $existingId)->exists(),
            'Old UDS rows must remain when Excel reading fails'
        );
    }

    public function test_transaction_rolls_back_uds_delete_when_sync_throws(): void
    {
        if (!Schema::hasTable('project_order_permit_uds')) {
            $this->markTestSkipped('project_order_permit_uds table missing. Run migrations.');
        }

        $company = Company::withoutGlobalScopes()->first();
        $project = ProjectManagement::withoutGlobalScopes()->first();

        if (!$company || !$project) {
            $this->markTestSkipped('Need company/project in DB.');
        }

        Storage::fake('public');
        $path = 'temp_imports/uds-rollback.xlsx';
        Storage::disk('public')->put($path, 'fake');

        $keepId = (string) Str::uuid();
        ProjectOrderPermitUds::withoutGlobalScopes()->insert([
            'id' => $keepId,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'name' => 'ROLLBACK-KEEP',
            'type_code' => '401',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    $this->makeRow('رقم أمر العمل', 'رمز'),
                    $this->makeRow('WO-RB-1', '401'),
                ],
            ]);

        $service = $this->createMock(ProjectOrderPermitService::class);
        $service->method('updateWorkOrdersFromUds')
            ->willThrowException(new \RuntimeException('sync failed'));

        $job = new ImportProjectOrderPermitUdsJob($path, (string) $project->id, (string) $company->id);

        try {
            $job->handle($service);
            $this->fail('Expected sync failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('sync failed', $e->getMessage());
        }

        $this->assertTrue(
            ProjectOrderPermitUds::withoutGlobalScopes()->where('id', $keepId)->exists(),
            'Delete+insert must roll back when updateWorkOrdersFromUds throws'
        );

        $this->assertFalse(
            ProjectOrderPermitUds::withoutGlobalScopes()
                ->where('project_id', $project->id)
                ->where('name', 'WO-RB-1')
                ->exists()
        );

        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_work_orders_from_uds_applies_contractor_and_consultant_fields(): void
    {
        if (!Schema::hasTable('project_order_permit_uds') || !Schema::hasTable('project_order_permit')) {
            $this->markTestSkipped('Required tables missing. Run migrations.');
        }

        $project = ProjectManagement::withoutGlobalScopes()->first();
        $company = $project
            ? Company::withoutGlobalScopes()->find($project->company_id)
            : null;

        if (!$project || !$company) {
            $this->markTestSkipped('Need project/company in DB.');
        }

        tenancy()->initialize($company);

        $department = OrderPermitDepartment::query()->firstOrCreate(['name' => 'توصيلات']);

        $orderPermit = OrderPermit::query()->where('code', '401')->first();

        if (!$orderPermit) {
            $this->markTestSkipped('OrderPermit code 401 not seeded.');
        }

        $orderPermit->update(['type' => '912']);

        $oldContractor = ProjectContractor::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'مقاول قديم-' . Str::random(4),
            'is_active' => true,
        ]);

        $newContractor = ProjectContractor::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'مقاول جديد-' . Str::random(4),
            'is_active' => true,
        ]);

        $workOrder = ProjectOrderPermit::query()->create([
            'project_id' => $project->id,
            'order_permit_id' => $orderPermit->id,
            'order_permit_department_id' => $department->id,
            'contractor_id' => $oldContractor->id,
            'name' => 'WO-UDS-' . Str::upper(Str::random(5)),
            'type' => 'إنشاء',
        ]);

        $now = now()->toDateTimeString();

        ProjectOrderPermitUds::withoutGlobalScopes()->insert([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'company_id' => $company->id,
            'name' => $workOrder->name,
            'type_code' => '401',
            'executing_entity' => 'جهة جديدة',
            'office' => 'توصيلات',
            'contractor_basket' => 'سلة مقاول',
            'consultant_current_basket' => 'سلة مقاول',
            'current_entity' => 'سلة مقاول',
            'contractor_last_procedure_code' => 'C1',
            'contractor_last_procedure_date' => '2024-05-01',
            'contractor_column_155_entry_date' => '2024-05-02',
            'material_balance_elec_contractor' => 'OK',
            'contractor_work_order_status' => 'جاري',
            'contractor_name' => $newContractor->name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ProjectOrderPermitUds::withoutGlobalScopes()->insert([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'company_id' => $company->id,
            'name' => $workOrder->name,
            'type_code' => '912',
            'consultant_current_basket' => 'سلة استشاري',
            'assigned_date' => '2024-06-01',
            'consultant_assignment_date' => '2024-06-01',
            'consultant_last_procedure_code' => 'S1',
            'consultant_last_procedure_date' => '2024-06-02',
            'consultant_column_155_entry_date' => '2024-06-03',
            'price' => 999.25,
            'consultant_price' => 999.25,
            'office' => 'توصيلات',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $updated = app(ProjectOrderPermitService::class)->updateWorkOrdersFromUds((string) $project->id);

        $this->assertGreaterThanOrEqual(1, $updated);

        $workOrder->refresh();

        $this->assertSame('جهة جديدة', $workOrder->executing_entity);
        $this->assertSame('سلة مقاول', $workOrder->contractor_basket);
        $this->assertSame('سلة استشاري', $workOrder->consultant_current_basket);
        $this->assertEquals(999.25, (float) $workOrder->price);
        $this->assertEquals(999.25, (float) $workOrder->consultant_price);
        $this->assertSame($newContractor->id, $workOrder->contractor_id);
        $this->assertNotNull($workOrder->last_row_update_at);
    }
}
