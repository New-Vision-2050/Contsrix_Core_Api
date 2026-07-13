<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Presenters\ProjectContractorPresenter;
use Modules\Project\ProjectManagement\Requests\StoreProjectContractorRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectContractorRequest;
use Modules\Project\ProjectManagement\Services\ProjectContractorService;

class ProjectContractorController extends Controller
{
    public function __construct(private readonly ProjectContractorService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $projectId = $request->route('project');

        $query = ProjectContractor::query()->where('is_active', true);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $projectContractors = $query->orderBy('name')->get(['id', 'name', 'number', 'mobile', 'notes']);

        return Json::items($projectContractors->map(fn ($projectContractor) => [
            'id' => $projectContractor->id,
            'name' => $projectContractor->name,
            'number' => $projectContractor->number,
            'mobile' => $projectContractor->mobile,
            'notes' => $projectContractor->notes,
        ])->all());
    }

    public function store(StoreProjectContractorRequest $request, string $project): JsonResponse
    {
        try {
            $projectContractor = $this->service->create($project, $request->validated(), $request->file('logo'));

            return Json::item((new ProjectContractorPresenter($projectContractor))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function show(string $project, string $id): JsonResponse
    {
        try {
            $projectContractor = $this->service->show($project, $id);

            return Json::item((new ProjectContractorPresenter($projectContractor))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateProjectContractorRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $projectContractor = $this->service->update($project, $id, $request->validated(), $request->file('logo'));

            return Json::item((new ProjectContractorPresenter($projectContractor))->getData());
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
}
