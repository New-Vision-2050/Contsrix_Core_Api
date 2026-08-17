<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Project\ProjectType\Presenters\SafetyRecordPresenter;
use Modules\Project\ProjectType\Presenters\SafetyReportPresenter;
use Modules\Project\ProjectType\Requests\EvaluateViolationsRequest;
use Modules\Project\ProjectType\Requests\FilterSafetyRecordsRequest;
use Modules\Project\ProjectType\Requests\StoreSafetyRecordRequest;
use Modules\Project\ProjectType\Requests\UpdateSafetyRecordRequest;
use Modules\Project\ProjectType\Services\SafetyService;
use Modules\Project\ProjectType\Services\SafetyViolationFormReportService;
use Modules\Project\ProjectType\Services\SafetyViolationReportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SafetyRecordController extends Controller
{
    public function __construct(
        private SafetyService $service,
        private SafetyViolationReportService $violationReportService,
        private SafetyViolationFormReportService $violationFormReportService,
    ) {}

    public function index(FilterSafetyRecordsRequest $request, string $project): JsonResponse
    {
        try {
            $paginator = $this->service->list(
                $project,
                $request->filters(),
                $request->perPage(),
                $request->sort()
            );

            return Json::items(
                mainItems: collect($paginator->items())
                    ->map(fn ($r) => (new SafetyRecordPresenter($r))->getData())
                    ->values()
                    ->all(),
                paginationSettings: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function report(FilterSafetyRecordsRequest $request, string $project): JsonResponse
    {
        try {
            $items = $this->service->report($project, $request->filters());

            return Json::items(
                $items->map(fn ($item) => (new SafetyReportPresenter($item))->getData())->toArray()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function inbox(FilterSafetyRecordsRequest $request): JsonResponse
    {
        try {
            $paginator = $this->service->inbox(
                (string) auth()->id(),
                $request->filters(),
                $request->perPage(),
                $request->sort()
            );

            return Json::items(
                mainItems: collect($paginator->items())
                    ->map(fn ($r) => (new SafetyRecordPresenter($r))->getData())
                    ->values()
                    ->all(),
                paginationSettings: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
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
                $request->violationsWithImages(),
                (string) auth()->id()
            );

            return Json::item((new SafetyRecordPresenter($record))->getData());
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function violationReport(string $project, string $id): Response|BinaryFileResponse|JsonResponse
    {
        try {
            return $this->violationReportService->download($project, $id);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function violationFormReport(string $project, string $id): Response|JsonResponse
    {
        try {
            return $this->violationFormReportService->download($project, $id);
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

        return Json::error($e->getMessage(), $httpStatus, null, [], $httpStatus);
    }
}
