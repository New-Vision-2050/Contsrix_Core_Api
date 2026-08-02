<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusUpdate;
use Modules\Project\ProjectManagement\Services\ProjectPCloudExportService;

class ProjectPCloudExportServiceTest extends BaseAttendanceReportTestCase
{
    public function test_it_exports_notification_media_to_project_maintenance_notification_folders(): void
    {
        Storage::fake('public');
        config([
            'media-library.disk_name' => 'public',
            'pcloud.enabled' => true,
            'pcloud.email' => 'user@example.test',
            'pcloud.password' => 'secret-password',
            'pcloud.root_folder' => 'Constrix Archive',
            'pcloud.dispatch' => 'sync',
            'pcloud.default_api_host' => 'https://api.pcloud.com',
            'pcloud.timeout' => 5,
        ]);

        $folders = [];
        $uploads = [];
        $nextFolderId = 1000;

        Http::fake(function (Request $request) use (&$folders, &$uploads, &$nextFolderId) {
            if (str_contains($request->url(), '/getdigest')) {
                return Http::response(['result' => 0, 'digest' => 'digest-token']);
            }

            if (str_contains($request->url(), '/createfolderifnotexists')) {
                $folderId = $nextFolderId++;
                $folders[] = [
                    'parent' => (int) $request['folderid'],
                    'name' => $request['name'],
                    'folderid' => $folderId,
                ];

                return Http::response([
                    'result' => 0,
                    'created' => false,
                    'metadata' => [
                        'folderid' => $folderId,
                        'name' => $request['name'],
                    ],
                ]);
            }

            if (str_contains($request->url(), '/uploadfile')) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

                $uploads[] = [
                    'folderid' => (int) ($query['folderid'] ?? 0),
                    'filename' => $query['filename'] ?? null,
                    'renameifexists' => (int) ($query['renameifexists'] ?? 0),
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type')[0] ?? null,
                    'has_body' => $request->body() !== '',
                ];

                return Http::response([
                    'result' => 0,
                    'fileids' => [2000],
                    'metadata' => [
                        ['fileid' => 2000, 'name' => 'site-photo.pdf'],
                    ],
                ]);
            }

            return Http::response(['result' => 0]);
        });

        $project = $this->createProject('PCloud Service Project');
        $notification = $this->createNotification($project, '4120899397');
        $task = $this->createTask($project, $notification);
        $notification->update(['employee_task_request_id' => $task->id]);
        $siteStatusUpdate = $this->createSiteStatusUpdate($notification, $task);

        $siteStatusUpdate
            ->addMedia(UploadedFile::fake()->create('site-photo.pdf', 12, 'application/pdf'))
            ->usingFileName('site-photo.pdf')
            ->withCustomProperties(['file_path' => 'pcloud-export-test'])
            ->toMediaCollection('attachments', 'public');

        $result = app(ProjectPCloudExportService::class)->export($project, 'run-test');

        $this->assertSame([
            ['parent' => 0, 'name' => 'Constrix Archive', 'folderid' => 1000],
            ['parent' => 1000, 'name' => 'Attendance Report Company', 'folderid' => 1001],
            ['parent' => 1001, 'name' => 'المشاريع', 'folderid' => 1002],
            ['parent' => 1002, 'name' => 'PCloud Service Project', 'folderid' => 1003],
            ['parent' => 1003, 'name' => 'الصيانة والطوارئ', 'folderid' => 1004],
            ['parent' => 1004, 'name' => '4120899397', 'folderid' => 1005],
        ], $folders);

        $this->assertSame([
            [
                'folderid' => 1005,
                'filename' => 'site-photo.pdf',
                'renameifexists' => 1,
                'method' => 'PUT',
                'content_type' => 'application/pdf',
                'has_body' => true,
            ],
        ], $uploads);

        $this->assertSame('run-test', $result['run_id']);
        $this->assertSame((string) $project->id, $result['project_id']);
        $this->assertSame(6, $result['folders_created_or_found']);
        $this->assertSame(1, $result['files_uploaded']);
        $this->assertSame(0, $result['files_failed']);
        $this->assertSame('Constrix Archive/Attendance Report Company/المشاريع/PCloud Service Project/الصيانة والطوارئ', $result['path']);
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
            'status' => 'in_progress',
            'task_date' => '2026-08-02',
            'created_by_user_id' => $this->actor->id,
        ]);
    }

    private function createTask(ProjectManagement $project, ProjectNotification $notification): EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'user_id' => $this->employee->id,
            'serial_number' => 'PCL-'.Str::upper(Str::random(8)),
            'title' => 'PCloud export task',
            'project_id' => $project->id,
            'project_notification_id' => $notification->id,
            'is_project_notification' => true,
            'duration_hours' => 1,
            'task_date' => '2026-08-02',
            'task_latitude' => 30.0000000,
            'task_longitude' => 31.0000000,
            'status' => 'in_progress',
        ]);
    }

    private function createSiteStatusUpdate(
        ProjectNotification $notification,
        EmployeeTaskRequest $task,
    ): ProjectNotificationSiteStatusUpdate {
        return ProjectNotificationSiteStatusUpdate::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_notification_id' => $notification->id,
            'employee_task_request_id' => $task->id,
            'update_date' => '2026-08-02',
            'update_time' => '14:17:18',
            'status' => 'approved',
            'requested_by' => $this->actor->id,
        ]);
    }
}
