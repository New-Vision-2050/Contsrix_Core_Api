<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitPresenter;
use Modules\Project\ProjectType\Requests\CreateProjectOrderPermitRequest;
use Modules\Project\ProjectType\Requests\UpdateProjectOrderPermitRequest;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;
use Modules\Project\ProjectType\Services\OrderPermitExcelImportService;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectType\Jobs\ImportOrderPermitsJob;
use Modules\Project\ProjectType\Models\UdsExcelSheet;
use Modules\Shared\Media\Services\FileUploadService;

class ProjectOrderPermitController extends Controller
{
    public function __construct(private readonly ProjectOrderPermitService $service)
    {
    }


    public function index(Request $request, string $project): JsonResponse
    {
        try {
            $items = $this->service->list(
                $project,
                $request->input('department_id')
            );

            return Json::items(
                $items->map(fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(true))->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function all(Request $request): JsonResponse
    {
        try {
            $items = $this->service->listAll();

            return Json::items(
                $items->map(fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(true))->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }


    // public function getByDepartment(Request $request, string $project, int $departmentId): JsonResponse
    // {
    //     try {
    //         $items = $this->service->listByDepartment($project, $departmentId);

    //         return Json::items(
    //             $items->map(fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(true))->toArray()
    //         );
    //     } catch (\Exception $e) {
    //         return Json::error($e->getMessage(), 500);
    //     }
    // }
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

    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:102400',
        ]);

        try {
            $file = $request->file('file');
            $projectId = $request->route('project');
            $companyId = tenant('id');

            $path = $file->store('temp_imports', 'public');
            $job = new ImportOrderPermitsJob($path);
            dispatch($job);

            $udsSheet = UdsExcelSheet::firstOrCreate([
                'project_id' => $projectId,
                'company_id' => $companyId,
            ]);

            $udsSheet->clearMediaCollection('uds_sheets');
            app(FileUploadService::class)->uploadFile(
                $udsSheet,
                $file,
                'temp_imports',
                'uds_sheets',
                'public'
            );

            return response()->json([
                'message' => 'جاري تحديث البيانات في الخلفية',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
