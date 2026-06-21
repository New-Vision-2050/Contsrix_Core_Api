<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Presenters\AttendanceDashboardPresenter;
use Modules\Attendance\Presenters\AttendanceReportRowPresenter;
use Modules\Attendance\Requests\AttendanceReportRequest;
use Modules\Attendance\Services\AttendanceDashboardService;
use Modules\Attendance\Services\AttendanceReportService;
use Modules\User\Models\User;

class AttendanceReportController extends Controller
{
    public function __construct(
        private AttendanceDashboardService $dashboardService,
        private AttendanceReportService $reportService,
    ) {}

    public function index(AttendanceReportRequest $request): JsonResponse
    {
        $filters = $request->toDTO();
        $employee = $this->reportService->getEmployee($filters);
        $summary = $this->dashboardService->buildSummary($filters);
        $monthly = $this->reportService->listMonthlyReports($filters);

        $payload = array_merge(
            [
                'employee' => $this->presentEmployee($employee),
            ],
            (new AttendanceDashboardPresenter($summary))->getData(),
            [
                'monthly_reports' => [
                    'data' => AttendanceReportRowPresenter::collection($monthly['data']),
                    'pagination' => $monthly['pagination'],
                ],
            ],
        );

        return Json::item($payload);
    }

    private function presentEmployee(User $employee): array
    {
        return [
            'id' => (string) $employee->id,
            'name' => (string) $employee->name,
        ];
    }
}
