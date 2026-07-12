<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Models\Contractor;
use Modules\Project\ProjectManagement\Presenters\ContractorPresenter;
use Modules\Project\ProjectManagement\Requests\StoreContractorRequest;
use Modules\Project\ProjectManagement\Requests\UpdateContractorRequest;
use Modules\Project\ProjectManagement\Services\ContractorService;

class ContractorController extends Controller
{
    public function __construct(private readonly ContractorService $service)
    {
    }

    /**
     * GET /api/v1/projects/notifications/contractors
     */
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->route('project');

        $query = Contractor::query()->where('is_active', true);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $contractors = $query->orderBy('name')->get(['id', 'name', 'number', 'mobile', 'notes']);

        return Json::items($contractors->map(fn ($contractor) => [
            'id'     => $contractor->id,
            'name'   => $contractor->name,
            'number' => $contractor->number,
            'mobile' => $contractor->mobile,
            'notes'  => $contractor->notes,
        ])->all());
    }

    public function store(StoreContractorRequest $request, string $project): JsonResponse
    {
        try {
            $contractor = $this->service->create($project, $request->validated(), $request->file('logo'));

            return Json::item((new ContractorPresenter($contractor))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function show(string $project, string $id): JsonResponse
    {
        try {
            $contractor = $this->service->show($project, $id);

            return Json::item((new ContractorPresenter($contractor))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateContractorRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $contractor = $this->service->update($project, $id, $request->validated(), $request->file('logo'));

            return Json::item((new ContractorPresenter($contractor))->getData());
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
