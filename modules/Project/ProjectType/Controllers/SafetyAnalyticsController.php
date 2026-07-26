<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\SafetyAnalyticsPresenter;
use Modules\Project\ProjectType\Services\SafetyAnalyticsService;
use Throwable;

class SafetyAnalyticsController extends Controller
{
    public function __construct(private SafetyAnalyticsService $service) {}

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

    public function topViolations(): JsonResponse
    {
        try {
            return Json::items(
                $this->service->topViolations()
                    ->map(fn (array $item) => SafetyAnalyticsPresenter::topViolation($item))
                    ->values()
                    ->all()
            );
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
