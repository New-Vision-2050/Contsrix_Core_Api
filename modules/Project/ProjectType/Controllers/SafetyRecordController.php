<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\SafetyRecordPresenter;
use Modules\Project\ProjectType\Requests\EvaluateViolationsRequest;
use Modules\Project\ProjectType\Requests\StoreSafetyRecordRequest;
use Modules\Project\ProjectType\Requests\UpdateSafetyRecordRequest;
use Modules\Project\ProjectType\Services\SafetyService;

class SafetyRecordController extends Controller
{
    public function __construct(private SafetyService $service) {}

    public function index(string $project): JsonResponse
    {
        try {
            $records = $this->service->list($project);

            return Json::items(
                $records->map(fn ($r) => (new SafetyRecordPresenter($r))->getData())->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function inbox(): JsonResponse
    {
        try {
            $records = $this->service->inbox((string) auth()->id());

            return Json::items(
                $records->map(fn ($r) => (new SafetyRecordPresenter($r))->getData())->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function store(StoreSafetyRecordRequest $request, string $project): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['project_id'] = $project;
            $records = $this->service->create($data);

            return Json::items(
                array_map(fn ($r) => (new SafetyRecordPresenter($r))->getData(), $records)
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function show(string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->show($project, $id);

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function update(UpdateSafetyRecordRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->update($project, $id, $request->validated());

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function evaluateViolations(EvaluateViolationsRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->evaluateViolations(
                $project,
                $id,
                $request->input('violations', []),
                (string) auth()->id()
            );

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    public function destroy(string $project, string $id): JsonResponse
    {
        try {
            $this->service->delete($project, $id);

            return Json::deleted();
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }
}
