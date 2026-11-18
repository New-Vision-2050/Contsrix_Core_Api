<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectType\Exports\ProjectOrderPermitExport;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Presenters\ProjectContractorPresenter;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitPresenter;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitNoteLogPresenter;
use Modules\Project\ProjectType\Requests\CreateProjectOrderPermitRequest;
use Modules\Project\ProjectType\Requests\SearchProjectOrderPermitUdsRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectOrderPermitRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectOrderPermitStatusRequest;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;
use Illuminate\Support\Str;
use Modules\Project\ProjectType\Jobs\ImportProjectWorkOrdersJob;
use Modules\Project\ProjectType\Jobs\ImportProjectOrderPermitUdsJob;
use Modules\Project\ProjectType\Exports\WorkOrderImportTemplateExport;
use Modules\Project\ProjectType\Imports\InvalidWorkOrderExcelHeaderException;
use Modules\Project\ProjectType\Imports\InvalidUdsExcelHeaderException;
use Modules\Project\ProjectType\Imports\UdsExcelHeaderReader;
use Modules\Project\ProjectType\Imports\UdsExcelHeaderValidator;
use Modules\Project\ProjectType\Imports\UdsExcelOfficialHeader;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderReader;
use Modules\Project\ProjectType\Imports\WorkOrderExcelHeaderValidator;
use Modules\Project\ProjectType\Imports\WorkOrderExcelOfficialHeader;
use Modules\Project\ProjectType\Models\ProjectWorkOrderExcelImport;
use Modules\Project\ProjectType\Support\WorkOrderExcelImportLogger;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectOrderPermitController extends Controller
{
    public function __construct(private readonly ProjectOrderPermitService $service)
    {
    }


    public function index(Request $request, string $project): JsonResponse
    {
        try {
            $items = $this->service->list($project, $request->only('order_permit_department_id'));

            return Json::items(
                $items->map(fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(true))->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function exportConstructionData(Request $request, string $project)
    {
        try {
            return Excel::download(
                new ProjectOrderPermitExport(
                    $this->service,
                    $project,
                    $request->only('order_permit_department_id'),
                ),
                'order-permits.xlsx',
            );
        } catch (ModelNotFoundException $e) {
            return Json::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function searchUds(SearchProjectOrderPermitUdsRequest $request, string $project): JsonResponse
    {
        try {
            $data = $this->service->searchUds(
                $project,
                $request->name(),
                $request->orderPermitId(),
            );

            if ($data['contractor'] instanceof ProjectContractor) {
                $data['contractor'] = (new ProjectContractorPresenter($data['contractor']))->getData();
            }

            return Json::item($data);
        } catch (ModelNotFoundException $e) {
            return Json::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function all(Request $request): JsonResponse
    {
        try {
            $items = $this->service->listAll($request->only('order_permit_department_id'));

            return Json::items(
                $items->map(fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(true))->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }


    public function store(CreateProjectOrderPermitRequest $request): JsonResponse
    {
        $items = $this->service->createMany($request->validated());

        return Json::items(
            array_map(
                static fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(),
                $items
            )
        );
    }


    public function show(string $project, string $id): JsonResponse
    {
        try {
            $item = $this->service->show($project, $id);

            return Json::item((new ProjectOrderPermitPresenter($item))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }


    public function update(UpdateProjectOrderPermitRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $item = $this->service->update($project, $id, $request->validated());

            return Json::item((new ProjectOrderPermitPresenter($item))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }


    public function destroy(string $project, string $id): JsonResponse
    {
        try {
            $this->service->delete($project, $id);

            return Json::deleted();
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function updateStatuses(UpdateProjectOrderPermitStatusRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $item = $this->service->updateStatuses($project, $id, $request->validated());

            return Json::item((new ProjectOrderPermitPresenter($item))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function noteLogs(string $project, string $id): JsonResponse
    {
        try {
            $logs = $this->service->getNoteLogs($project, $id);

            return Json::items(
                $logs->map(fn ($log) => (new ProjectOrderPermitNoteLogPresenter($log))->getData())->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        $path = UdsExcelOfficialHeader::templateAbsolutePath();

        abort_unless(is_file($path), 404, 'Official UDS template is missing.');

        return response()->download($path, UdsExcelOfficialHeader::DOWNLOAD_NAME, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:102400',
        ]);

        try {
            $file = $request->file('file');
            $headerRow = (new UdsExcelHeaderReader())->readFirstRow((string) $file->getRealPath());
            (new UdsExcelHeaderValidator())->validate($headerRow);

            $projectId = (string) $request->route('project');
            $companyId = (string) tenant('id');

            $path = $file->store('temp_imports', 'public');

            dispatch(new ImportProjectOrderPermitUdsJob($path, $projectId, $companyId));

            return response()->json([
                'message' => 'جاري تحديث البيانات في الخلفية',
            ]);
        } catch (InvalidUdsExcelHeaderException $e) {
            return Json::error(
                $e->getMessage(),
                422,
                'InvalidUdsTemplate',
                ['mismatches' => $e->mismatches()],
                422,
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadWorkOrderImportTemplate()
    {
        return Excel::download(
            new WorkOrderImportTemplateExport,
            WorkOrderExcelOfficialHeader::DOWNLOAD_NAME,
        );
    }

    public function importWorkOrdersExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:102400'],
        ]);

        $projectId = (string) $request->route('project');
        $companyId = (string) tenant('id');
        $userId = auth()->id() ? (string) auth()->id() : null;
        ProjectManagement::query()->findOrFail($projectId);

        $importId = (string) Str::uuid();
        $file = $request->file('file');

        WorkOrderExcelImportLogger::info('import_request_received', [
            'import_id' => $importId,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'user_id' => $userId,
            'tenant_id' => $companyId,
            'original_filename' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize(),
            'extension' => $file?->getClientOriginalExtension(),
            'mime_type' => $file?->getMimeType(),
            'status' => 'received',
        ]);

        try {
            $headerRow = (new WorkOrderExcelHeaderReader())->readFirstRow((string) $file->getRealPath());
            (new WorkOrderExcelHeaderValidator())->validate($headerRow);

            WorkOrderExcelImportLogger::info('file_validation_passed', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => 'validated',
            ]);

            $path = $file->store('temp_imports', 'local');

            WorkOrderExcelImportLogger::info('file_stored', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'stored_path' => $path,
                'disk' => 'local',
                'status' => 'stored',
            ]);

            $import = ProjectWorkOrderExcelImport::query()->create([
                'id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => ProjectWorkOrderExcelImport::STATUS_QUEUED,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            try {
                dispatch(new ImportProjectWorkOrdersJob(
                    $importId,
                    $path,
                    $projectId,
                    $companyId,
                    $userId,
                ));
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                $import->forceFill([
                    'status' => ProjectWorkOrderExcelImport::STATUS_FAILED,
                    'failure_reason' => $e->getMessage(),
                    'finished_at' => now(),
                ])->save();

                WorkOrderExcelImportLogger::error('queue_dispatch_failed', [
                    'import_id' => $importId,
                    'project_id' => $projectId,
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'status' => 'failed',
                ], $e);

                throw $e;
            }

            WorkOrderExcelImportLogger::info('queue_dispatched', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => 'queued',
            ]);

            return response()->json([
                'message' => 'تم قبول الملف. جاري تحديث أوامر العمل في الخلفية (تحديث فقط، بدون إضافة سجلات جديدة).',
                'import_id' => $importId,
                'status' => ProjectWorkOrderExcelImport::STATUS_QUEUED,
                'status_url' => url("/api/v1/projects/{$projectId}/order-permits/import-construction/{$importId}"),
            ]);
        } catch (InvalidWorkOrderExcelHeaderException $e) {
            WorkOrderExcelImportLogger::warning('header_validation_failed', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => 'rejected',
                'error_message' => $e->getMessage(),
                'mismatches' => $e->mismatches(),
            ]);

            return Json::error(
                $e->getMessage(),
                422,
                'InvalidWorkOrdersTemplate',
                ['mismatches' => $e->mismatches(), 'import_id' => $importId],
                422,
            );
        } catch (\Exception $e) {
            WorkOrderExcelImportLogger::error('import_request_failed', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $userId,
                'status' => 'failed',
            ], $e);

            return response()->json(['error' => $e->getMessage(), 'import_id' => $importId], 500);
        }
    }

    public function showWorkOrderExcelImport(string $project, string $importId): JsonResponse
    {
        $companyId = (string) tenant('id');

        $import = ProjectWorkOrderExcelImport::query()
            ->where('id', $importId)
            ->where('project_id', $project)
            ->where('company_id', $companyId)
            ->firstOrFail();

        return response()->json([
            'data' => $import->toStatusPayload(),
        ]);
    }

    public function updateFromUds(Request $request, string $project, string $name): JsonResponse
    {
        $request->validate([
            'order_permit_id' => 'required',
        ]);

        try {
            $item = $this->service->updateFromUds(
                $project,
                $name,
                $request->query('order_permit_id')
            );

            return Json::item((new ProjectOrderPermitPresenter($item))->getData());
        } catch (ModelNotFoundException $e) {
            return Json::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

}
