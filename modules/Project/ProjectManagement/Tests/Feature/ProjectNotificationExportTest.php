<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Exports\ProjectNotificationExport;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\RoleAndPermission\Enums\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;

class ProjectNotificationExportTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        setPermissionsTeamId($this->company->id);

        $permission = Permission::PROJECT_NOTIFICATION_EXPORT();
        SpatiePermission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'api'],
            ['name' => $permission, 'guard_name' => 'api', 'company_id' => $this->company->id],
        );

        $this->actor->givePermissionTo($permission);
    }

    public function test_export_with_project_filter_returns_all_matching_notifications_and_ignores_pagination(): void
    {
        Excel::fake();

        $project = $this->createProject('Filtered Project');
        $otherProject = $this->createProject('Other Project');

        $first = $this->createNotification($project, 'NTF-EXPORT-001');
        $second = $this->createNotification($project, 'NTF-EXPORT-002');
        $other = $this->createNotification($otherProject, 'NTF-EXPORT-003');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/notifications/export', [
                'project_id' => $project->id,
                'page' => 1,
                'per_page' => 1,
            ]);

        $response->assertOk();

        Excel::assertDownloaded('project_notifications.xlsx', function (ProjectNotificationExport $export) use ($first, $second, $other) {
            $ids = $export->collection()->pluck('id')->all();

            return count($ids) === 2
                && in_array($first->id, $ids, true)
                && in_array($second->id, $ids, true)
                && ! in_array($other->id, $ids, true);
        });
    }

    public function test_export_ids_are_intersected_with_active_filters(): void
    {
        Excel::fake();

        $project = $this->createProject('Selected Project');
        $otherProject = $this->createProject('Outside Project');

        $selected = $this->createNotification($project, 'NTF-SELECTED-001');
        $notSelected = $this->createNotification($project, 'NTF-SELECTED-002');
        $selectedButFilteredOut = $this->createNotification($otherProject, 'NTF-SELECTED-003');

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/notifications/export', [
                'project_id' => $project->id,
                'ids' => [$selected->id, $selectedButFilteredOut->id],
            ]);

        $response->assertOk();

        Excel::assertDownloaded('project_notifications.xlsx', function (ProjectNotificationExport $export) use ($selected, $notSelected, $selectedButFilteredOut) {
            $ids = $export->collection()->pluck('id')->all();

            return $ids === [$selected->id]
                && ! in_array($notSelected->id, $ids, true)
                && ! in_array($selectedButFilteredOut->id, $ids, true);
        });
    }

    public function test_export_rejects_invalid_format_and_invalid_ids(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/notifications/export', [
                'format' => 'pdf',
                'ids' => ['not-a-uuid'],
            ]);

        $response->assertUnprocessable();
    }

    public function test_export_uses_arabic_screenshot_headings_in_order(): void
    {
        $this->assertSame([
            'رقم الإشعار',
            'حالة الإشعار',
            'نوع الإشعار',
            'حالة العمل',
            'الموقت',
            'تاريخ أخر تحديث بالموقع',
            'التاريخ',
            'اخر ملاحظة',
            'المقاول',
            'اسم الاستشاري',
            'المهندس',
            'الموقع',
        ], (new ProjectNotificationExport)->headings());
    }

    private function createProject(string $name): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
    }

    private function createNotification(ProjectManagement $project, string $number): ProjectNotification
    {
        return ProjectNotification::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'notification_number' => $number,
            'notification_type' => 'جهد منخفض كابلات ارضي',
            'status' => 'pending',
            'task_date' => '2026-08-02',
            'created_by_user_id' => $this->actor->id,
        ]);
    }
}
