<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Exports\WorkOrderImportTemplateExport;
use Modules\Project\ProjectType\Imports\InvalidWorkOrderExcelHeaderException;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderValidator;
use Modules\Project\ProjectType\Imports\WorkOrderExcelOfficialHeader;
use Modules\Project\ProjectType\Jobs\ImportProjectWorkOrdersJob;
use Modules\Project\ProjectType\Models\ConnectionCompletionPhase;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermitNoteLog;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Models\ProjectWorkOrderExcelImport;
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

    public function test_template_export_headings_match_official_import_header(): void
    {
        $export = new WorkOrderImportTemplateExport;

        $this->assertSame(WorkOrderExcelOfficialHeader::COLUMNS, $export->headings());
        $this->assertSame([], $export->array());
    }

    public function test_job_marks_failed_and_deletes_file_when_excel_read_fails(): void
    {
        if (! Schema::hasTable('project_work_order_excel_imports')) {
            $this->markTestSkipped('project_work_order_excel_imports table missing');
        }

        Storage::fake('local');
        $path = 'temp_imports/work-orders-fail.xlsx';
        Storage::disk('local')->put($path, 'fake');

        $companyId = \Modules\Company\CompanyCore\Models\Company::query()->value('id');
        if ($companyId === null) {
            $this->markTestSkipped('Need a company to initialize tenancy in the import job.');
        }

        $importId = (string) Str::uuid();
        ProjectWorkOrderExcelImport::query()->create([
            'id' => $importId,
            'project_id' => (string) Str::uuid(),
            'company_id' => (string) $companyId,
            'status' => ProjectWorkOrderExcelImport::STATUS_QUEUED,
            'stored_path' => $path,
        ]);

        Excel::shouldReceive('toArray')
            ->once()
            ->andThrow(new \RuntimeException('corrupt excel'));

        $job = new ImportProjectWorkOrdersJob($importId, $path, 'project-1', (string) $companyId);

        try {
            $job->handle(app(ProjectWorkOrderExcelImportService::class));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('corrupt excel', $e->getMessage());
        }

        Storage::disk('local')->assertMissing($path);
        $this->assertSame(
            ProjectWorkOrderExcelImport::STATUS_FAILED,
            ProjectWorkOrderExcelImport::query()->find($importId)?->status
        );
    }

    public function test_import_updates_only_and_never_inserts(): void
    {
        $context = $this->prepareImportContext(
            departmentName: 'مشاريع',
            useConnectionPhases: false,
        );

        if ($context === null) {
            return;
        }

        [
            'employee' => $employee,
            'projectEmployee' => $projectEmployee,
            'exactPermit' => $exactPermit,
            'wrongPermit' => $wrongPermit,
            'department' => $department,
            'phase' => $phase,
            'status' => $status,
            'workOrderName' => $workOrderName,
        ] = $context;

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
            'order_permit_department_id' => $department->id,
            'description_details' => 'before wrong code',
        ]);

        $beforeCount = ProjectOrderPermit::query()
            ->where('project_id', $projectEmployee->project_id)
            ->count();

        $row = [
            $workOrderName,
            (string) $exactPermit->code,
            'ملاحظة تحديث',
            $employee->name,
            $phase->name,
            $status->name,
            100,
            90,
            200,
            180,
            'after exact',
            'لا توجد ملاحظات',
            '2026-09-15',
        ];

        $importId = (string) Str::uuid();
        $result = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $row],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
            $importId,
            (string) $employee->id,
            $employee->name,
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(
            $beforeCount,
            ProjectOrderPermit::query()->where('project_id', $projectEmployee->project_id)->count()
        );

        $exact->refresh();
        $this->assertSame('after exact', $exact->description_details);
        $this->assertSame('ملاحظة تحديث', $exact->note_from_departments_to_permit);
        $this->assertSame((string) $employee->id, (string) $exact->employee_id);
        $this->assertSame($phase->id, $exact->project_completion_phase_id);
        $this->assertSame($status->id, $exact->project_phase_status_id);
        $this->assertSame('before wrong code', $wrongCode->fresh()->description_details);

        $this->assertTrue(
            ProjectOrderPermitNoteLog::query()
                ->where('project_order_permit_id', $exact->id)
                ->where('note', 'ملاحظة تحديث')
                ->where('type', ProjectOrderPermitNoteLog::TYPE_DEPARTMENTS_TO_PERMIT)
                ->exists()
        );

        $missingRow = $row;
        $missingRow[0] = 'MISSING-'.Str::random(8);
        $missingResult = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $missingRow],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
            (string) Str::uuid(),
        );

        $this->assertSame(0, $missingResult['updated']);
        $this->assertSame(1, $missingResult['skipped']);
        $this->assertSame(
            $beforeCount,
            ProjectOrderPermit::query()->where('project_id', $projectEmployee->project_id)->count(),
            'Missing match must not insert a new ProjectOrderPermit'
        );

        $missingEngineerRow = $row;
        $missingEngineerRow[3] = 'مهندس غير موجود '.Str::random(8);
        $missingEngineerRow[10] = 'updated without engineer';
        $previousEmployeeId = $exact->fresh()->employee_id;

        $missingEngineerResult = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $missingEngineerRow],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
            (string) Str::uuid(),
        );

        $this->assertSame(1, $missingEngineerResult['updated']);
        $this->assertSame(0, $missingEngineerResult['skipped']);
        $this->assertNotEmpty($missingEngineerResult['warnings']);
        $exact->refresh();
        $this->assertSame('updated without engineer', $exact->description_details);
        $this->assertSame((string) $previousEmployeeId, (string) $exact->employee_id);
    }

    public function test_import_updates_connection_department_phase_fields(): void
    {
        $context = $this->prepareImportContext(
            departmentName: 'توصيلات',
            useConnectionPhases: true,
        );

        if ($context === null) {
            return;
        }

        [
            'employee' => $employee,
            'projectEmployee' => $projectEmployee,
            'exactPermit' => $exactPermit,
            'department' => $department,
            'phase' => $phase,
            'status' => $status,
            'workOrderName' => $workOrderName,
        ] = $context;

        $workOrder = ProjectOrderPermit::query()->create([
            'project_id' => $projectEmployee->project_id,
            'name' => $workOrderName,
            'order_permit_id' => $exactPermit->id,
            'order_permit_department_id' => $department->id,
            'description_details' => 'before connection',
            'project_completion_phase_id' => null,
            'project_phase_status_id' => null,
        ]);

        $row = [
            $workOrderName,
            (string) $exactPermit->code,
            null,
            $employee->name,
            $phase->name,
            $status->name,
            null,
            null,
            null,
            null,
            'after connection',
            'إفادة',
            '2026-11-01',
        ];

        $result = app(ProjectWorkOrderExcelImportService::class)->importRows(
            [WorkOrderExcelOfficialHeader::COLUMNS, $row],
            (string) $projectEmployee->project_id,
            (string) $projectEmployee->company_id,
            (string) Str::uuid(),
        );

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);

        $workOrder->refresh();
        $this->assertSame('after connection', $workOrder->description_details);
        $this->assertSame($phase->id, $workOrder->connection_completion_phase_id);
        $this->assertSame($status->id, $workOrder->connection_phase_status_id);
        $this->assertNull($workOrder->project_completion_phase_id);
        $this->assertNull($workOrder->project_phase_status_id);

        $this->assertFalse(
            ProjectOrderPermitNoteLog::query()
                ->where('project_order_permit_id', $workOrder->id)
                ->exists(),
            'Empty note must not create a Note Log'
        );
    }

    /**
     * @return array{
     *     employee: \Modules\User\Models\User,
     *     projectEmployee: ProjectEmployee,
     *     exactPermit: OrderPermit,
     *     wrongPermit: OrderPermit,
     *     department: OrderPermitDepartment,
     *     phase: ProjectCompletionPhase|ConnectionCompletionPhase,
     *     status: ProjectPhaseStatus|ConnectionPhaseStatus,
     *     workOrderName: string
     * }|null
     */
    private function prepareImportContext(string $departmentName, bool $useConnectionPhases): ?array
    {
        foreach ([
            'project_order_permit',
            'order_permit',
            'project_employees',
            'project_completion_phases',
            'project_phase_statuses',
            'connection_completion_phases',
            'connection_phase_statuses',
            'project_order_permit_note_logs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table missing: {$table}");

                return null;
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

            return null;
        }

        $employee = $projectEmployee->user;
        $exactPermit = $permits[0];
        $wrongPermit = $permits[1];
        $department = OrderPermitDepartment::query()->firstOrCreate([
            'name' => $departmentName,
        ]);

        $exactPermit->update(['order_permit_department_id' => $department->id]);

        $phaseName = 'مرحلة اختبار '.Str::random(8);
        $statusName = 'حالة اختبار '.Str::random(8);

        if ($useConnectionPhases) {
            $phase = ConnectionCompletionPhase::query()->create([
                'order_permit_department_id' => $department->id,
                'name' => $phaseName,
            ]);
            $status = ConnectionPhaseStatus::query()->create([
                'connection_completion_phase_id' => $phase->id,
                'name' => $statusName,
            ]);
        } else {
            $phase = ProjectCompletionPhase::query()->create([
                'order_permit_department_id' => $department->id,
                'name' => $phaseName,
            ]);
            $status = ProjectPhaseStatus::query()->create([
                'project_completion_phase_id' => $phase->id,
                'name' => $statusName,
            ]);
        }

        return [
            'employee' => $employee,
            'projectEmployee' => $projectEmployee,
            'exactPermit' => $exactPermit,
            'wrongPermit' => $wrongPermit,
            'department' => $department,
            'phase' => $phase,
            'status' => $status,
            'workOrderName' => 'WO-'.Str::random(10),
        ];
    }
}
