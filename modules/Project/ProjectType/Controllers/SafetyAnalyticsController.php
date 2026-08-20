<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Project\ProjectType\Exceptions\SafetyException;
use Modules\Project\ProjectType\Presenters\SafetyAnalyticsPresenter;
use Modules\Project\ProjectType\Presenters\SafetyWeeklyReportPresenter;
use Modules\Project\ProjectType\Requests\GenerateWeeklySafetyReportRequest;
use Modules\Project\ProjectType\Requests\SafetyAnalyticsDateRangeRequest;
use Modules\Project\ProjectType\Services\SafetyAnalyticsService;
use Modules\Project\ProjectType\Services\SafetyWeeklyReportService;
use Throwable;

class SafetyAnalyticsController extends Controller
{
    public function __construct(
        private SafetyAnalyticsService $service,
        private SafetyWeeklyReportService $weeklyReportService,
    ) {}

    public function overall(string $project): JsonResponse
    {
        try {
            return Json::item(
                SafetyAnalyticsPresenter::overall($this->service->overall($project))
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function compliant(string $project): JsonResponse
    {
        try {
            return Json::item(
                SafetyAnalyticsPresenter::compliant($this->service->compliant($project))
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function frequentViolations(string $project): JsonResponse
    {
        try {
            return Json::items(
                $this->service->frequentViolations($project)
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::frequentViolation($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function violationPerformance(string $project): JsonResponse
    {
        try {
            return Json::items(
                $this->service->violationPerformance($project)
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::violationPerformance($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function byContractorConsultant(string $project): JsonResponse
    {
        try {
            return Json::items(
                $this->service->byContractorConsultant($project)
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::byContractorConsultant($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function topViolations(Request $request): JsonResponse
    {
        try {
            $fromDate = $request->query('from_date');
            $toDate = $request->query('to_date');

            $from = is_string($fromDate) && $fromDate !== '' ? $fromDate : null;
            $to = is_string($toDate) && $toDate !== '' ? $toDate : null;

            if (($from !== null && $to === null) || ($from === null && $to !== null)) {
                return Json::error('يجب إرسال from_date و to_date معًا.', 422, null, [], 422);
            }

            if ($from !== null && $to !== null) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                    return Json::error('صيغة التاريخ يجب أن تكون Y-m-d.', 422, null, [], 422);
                }
                if ($to < $from) {
                    return Json::error('to_date يجب أن يكون بعد أو يساوي from_date.', 422, null, [], 422);
                }
            }

            return Json::items(
                $this->service->topViolations(5, $from, $to)
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::topViolation($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function globalViolationFrequencies(SafetyAnalyticsDateRangeRequest $request): JsonResponse
    {
        try {
            return Json::items(
                $this->service->globalViolationFrequencies($request->fromDate(), $request->toDate())
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::violationFrequency($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function contractorCompliance(SafetyAnalyticsDateRangeRequest $request, string $project): JsonResponse
    {
        try {
            return Json::items(
                $this->service->contractorCompliance($project, $request->fromDate(), $request->toDate())
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::contractorCompliance($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function contractorTopViolations(SafetyAnalyticsDateRangeRequest $request, string $project): JsonResponse
    {
        try {
            $contractorId = $request->contractorId();
            if ($contractorId === null) {
                return Json::error('contractor_id مطلوب.', 422, null, [], 422);
            }

            return Json::items(
                $this->service->contractorTopViolations(
                    $project,
                    $contractorId,
                    $request->fromDate(),
                    $request->toDate(),
                    $request->limit(5)
                )
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::violationFrequency($item))
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function weeklyReport(GenerateWeeklySafetyReportRequest $request, string $project): Response|JsonResponse
    {
        try {
            // Generate + persist, then stream PDF (existing UX + new persistence).
            return $this->weeklyReportService->download(
                $project,
                $request->fromDate(),
                $request->toDate()
            );
        } catch (SafetyException $e) {
            return $this->errorResponse($e);
        } catch (Throwable $e) {
            return $this->errorResponse(SafetyException::weeklyReportGenerationFailed());
        }
    }

    public function storeWeeklyReport(GenerateWeeklySafetyReportRequest $request, string $project): JsonResponse
    {
        try {
            $report = $this->weeklyReportService->createAndStore(
                $project,
                $request->fromDate(),
                $request->toDate()
            );

            return Json::item(
                (new SafetyWeeklyReportPresenter($report))->getData()
            );
        } catch (SafetyException $e) {
            return $this->errorResponse($e);
        } catch (Throwable $e) {
            return $this->errorResponse(SafetyException::weeklyReportGenerationFailed());
        }
    }

    public function listWeeklyReports(string $project): JsonResponse
    {
        try {
            $reports = $this->weeklyReportService->listByProject($project);

            return Json::items(
                $reports
                    ->map(fn ($report) => (new SafetyWeeklyReportPresenter($report))->getData())
                    ->values()
                    ->all()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function showWeeklyReport(string $project, string $id): JsonResponse
    {
        try {
            $report = $this->weeklyReportService->findForProject($project, $id);

            return Json::item(
                (new SafetyWeeklyReportPresenter($report))->getData()
            );
        } catch (SafetyException $e) {
            return $this->errorResponse($e);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function downloadWeeklyReport(string $project, string $id): Response|JsonResponse
    {
        try {
            return $this->weeklyReportService->downloadStored($project, $id);
        } catch (SafetyException $e) {
            return $this->errorResponse($e);
        } catch (Throwable $e) {
            return $this->errorResponse(SafetyException::weeklyReportFileMissing());
        }
    }

    private function errorResponse(Throwable $e): JsonResponse
    {
        $httpStatus = method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;

        return Json::error($e->getMessage(), $httpStatus, null, [], $httpStatus);
    }
}
