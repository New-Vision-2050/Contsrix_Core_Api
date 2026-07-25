<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\SafetyRecordPresenter;
use Modules\Project\ProjectType\Requests\StoreSafetyRecordRequest;
use Modules\Project\ProjectType\Requests\UpdateSafetyRecordRequest;
use Modules\Project\ProjectType\Requests\EvaluateViolationsRequest;
use Modules\Project\ProjectType\Services\SafetyService;

class SafetyRecordController extends Controller
{
    public function __construct(private SafetyService $service) {}

    public function index(Request $request, string $project): JsonResponse
    {
        $records = $this->service->list($project);
        return Json::items(
            $records->map(fn($r) => (new SafetyRecordPresenter($r))->getData())->toArray()
        );
    }

    public function inbox(): JsonResponse
    {
        $records = $this->service->inbox(auth()->id());
        return Json::items(
            $records->map(fn($r) => (new SafetyRecordPresenter($r))->getData())->toArray()
        );
    }

    public function store(StoreSafetyRecordRequest $request, string $project): JsonResponse
    {
        $data = $request->validated();
        $data['project_id'] = $project;
        $records = $this->service->create($data);
        return Json::items(
            array_map(fn($r) => (new SafetyRecordPresenter($r))->getData(), $records)
        );
    }

    public function show(string $project, string $id): JsonResponse
    {
        $record = $this->service->show($id);
        return Json::item((new SafetyRecordPresenter($record))->getData());
    }

    public function update(UpdateSafetyRecordRequest $request, string $project, string $id): JsonResponse
    {
        $record = $this->service->update($id, $request->validated());
        return Json::item((new SafetyRecordPresenter($record))->getData());
    }


    public function evaluateViolations(EvaluateViolationsRequest $request, string $project, string $id): JsonResponse
    {
        $record = $this->service->evaluateViolations($id, $request->input('violations', []));
        return Json::item((new SafetyRecordPresenter($record))->getData());
    }

    public function destroy(string $project, string $id): JsonResponse
    {
        $this->service->delete($id);
        return Json::deleted();
    }
}
