<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Imports\InvalidWorkOrderExcelHeaderException;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderValidator;
use Modules\Project\ProjectType\Imports\WorkOrderExcelOfficialHeader;
use Modules\Project\ProjectType\Jobs\ImportProjectWorkOrdersJob;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Services\ProjectWorkOrderExcelImportService;
use Tests\TestCase;

final class ProjectWorkOrderExcelImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_official_header_is_accepted_and_a_changed_header_is_rejected(): void
    {
        $validator = new WorkOrderExcelHeaderValidator;
        $validator->validate(WorkOrderExcelOfficialHeader::COLUMNS);

        $invalid = WorkOrderExcelOfficialHeader::COLUMNS;
        $invalid[0] = 'رقم مختلف';

        $this->expectException(InvalidWorkOrderExcelHeaderException::class);
        $validator->validate($invalid);
    }

    public function test_job_always_deletes_temporary_file_when_excel_read_fails(): void
    {
        Storage::fake('public');
        $path = 'temp_imports/work-orders-fail.xlsx';
        Storage::disk('public')->put($path, 'fake');

        Excel::shouldReceive('toArray')
            ->once()
            ->andThrow(new \RuntimeException('corrupt excel'));

        $job = new ImportProjectWorkOrdersJob($path, 'project-1', 'company-1');

        try {
            $job->handle(app(ProjectWorkOrderExcelImportService::class));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('corrupt excel', $e->getMessage());
        }

        Storage::disk('public')->assertMissing($path);
    }

    public function test_import_updates_only_the_exact_project_name_and_order_permit_code_match(): void
    {
        foreach ([
            'project_order_permit',
            'order_permit',
            'project_employees',
            'project_completion_phases',
            'project_phase_statuses',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table missing: {$table}");
            }
        }

        $projectEmployee = ProjectEmployee::query()
            ->whereNotNull('company_id')
            ->whereHas('user')
            ->first();
        $permits = OrderPermit::query()
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->get()
            ->unique('code')
            ->values();

        if (! $projectEmployee || $permits->count() < 2) {
            $this->markTestSkipped('Need a project employee and two distinct Order Permit codes.');
        }

        $employee = $projectEmployee->user;
        $exactPermit = $permits[0];
        $wrongPermit = $permits[1];
        $department = OrderPermitDepartment::query()->firstOrCreate([
            'name' => 'مشاريع',
        ]);
        $differentDepartment = OrderPermitDepartment::query()->create([
            'name' => 'قسم اختبار '.Str::random(8),
        ]);

        $exactPermit->update(['order_permit_department_id' => $department->id]);

        $phaseName = 'مرحلة اختبار '.Str::random(8);
        $statusName = 'حالة اختبار '.Str::random(8);
        $phase = ProjectCompletionPhase::query()->create([
            'order_permit_department_id' => $differentDepartment->id,
            'name' => $phaseName,
        ]);
        $status = ProjectPhaseStatus::query()->create([
            'project_completion_phase_id' => $phase->id,
            'name' => $statusName,
        ]);
        $workOrderName = 'WO-'.Str::random(10);

        $exact = ProjectOrderPermit::query()->create([
            'project_id' => $projectEmployee->project_id,
            'name' => $workOrderName,
            'order_permit_id' => $exactPermit->id,
            'order_permit_department_id' => $department->id,
            'description_details' => 'before exact',
        ]);
        $wrongCode = ProjectOrderPermit::query()->create([
            'project_id' => $projectEmployee->project_id,
            'name' => $workOrderName,
            'order_permit_id' => $wrongPermit->id,
            'description_details' => 'before wrong code',
        ]);

        $row = [
            $workOrderName,
            (string) $exactPermit->code,
            'تم الاستلام',
            $employee->name,
            $phaseName,
            $statusName,
            100,
            90,
            200,
            180,
            'after exact',
            'لا توجد ملاحظات',
            '2026-09-15',
        ];

        $result = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $row],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame('after exact', $exact->fresh()->description_details);
        $this->assertSame((string) $employee->id, (string) $exact->fresh()->employee_id);
        $this->assertSame($phase->id, $exact->fresh()->project_completion_phase_id);
        $this->assertSame($status->id, $exact->fresh()->project_phase_status_id);
        $this->assertSame('before wrong code', $wrongCode->fresh()->description_details);

        $missingPhaseRow = $row;
        $missingPhaseRow[4] = 'مرحلة غير موجودة '.Str::random(8);
        $missingPhaseRow[10] = 'updated with null phase';

        $missingPhaseResult = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $missingPhaseRow],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
        );

        $this->assertSame(1, $missingPhaseResult['updated']);
        $this->assertSame(0, $missingPhaseResult['skipped']);
        $this->assertSame('updated with null phase', $exact->fresh()->description_details);
        $this->assertNull($exact->fresh()->project_completion_phase_id);
        $this->assertNull($exact->fresh()->project_phase_status_id);

        $missingStatusRow = $row;
        $missingStatusRow[5] = 'حالة غير موجودة '.Str::random(8);
        $missingStatusRow[10] = 'updated with null status';

        $missingStatusResult = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $missingStatusRow],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
        );

        $this->assertSame(1, $missingStatusResult['updated']);
        $this->assertSame(0, $missingStatusResult['skipped']);
        $this->assertSame('updated with null status', $exact->fresh()->description_details);
        $this->assertSame($phase->id, $exact->fresh()->project_completion_phase_id);
        $this->assertNull($exact->fresh()->project_phase_status_id);
        $this->assertSame('before wrong code', $wrongCode->fresh()->description_details);

        $invalidEmployeeRow = $row;
        $invalidEmployeeRow[2] = 'must not be applied';
        $invalidEmployeeRow[3] = 'missing-'.Str::random(10);

        $failed = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $invalidEmployeeRow],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
        );

        $this->assertSame(0, $failed['updated']);
        $this->assertSame(1, $failed['skipped']);
        $this->assertSame('تم الاستلام', $exact->fresh()->note_from_departments_to_permit);
    }
}
