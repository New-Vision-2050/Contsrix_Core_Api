<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\SafetyRecordPresenter;
use Modules\Project\ProjectType\Presenters\SafetyReportPresenter;
use Modules\Project\ProjectType\Requests\EvaluateViolationsRequest;
use Modules\Project\ProjectType\Requests\StoreSafetyRecordRequest;
use Modules\Project\ProjectType\Requests\UpdateSafetyRecordRequest;
use Modules\Project\ProjectType\Services\SafetyService;
use Throwable;

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
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function report(string $project): JsonResponse
    {
        try {
            $items = $this->service->report($project);

            return Json::items(
                $items->map(fn ($item) => (new SafetyReportPresenter($item))->getData())->toArray()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function inbox(): JsonResponse
    {
        try {
            $records = $this->service->inbox((string) auth()->id());

            return Json::items(
                $records->map(fn ($r) => (new SafetyRecordPresenter($r))->getData())->toArray()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
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
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function show(string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->show($project, $id);

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function update(UpdateSafetyRecordRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->update($project, $id, $request->validated());

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function evaluateViolations(EvaluateViolationsRequest $request, string $project, string $id): JsonResponse
    {
        try {
            $record = $this->service->evaluateViolations(
                $project,
                $id,
                $request->input('violations', []),
                (string) auth()->id(),
                $request->file('images', [])
            );

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(string $project, string $id): JsonResponse
    {
        try {
            $this->service->delete($project, $id);

            return Json::deleted();
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(Throwable $e): JsonResponse
    {
        $httpStatus = method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;

        // Json::error($description, $code, $name, $data, $httpStatus)
        return Json::error($e->getMessage(), $httpStatus, null, [], $httpStatus);
    }
}
