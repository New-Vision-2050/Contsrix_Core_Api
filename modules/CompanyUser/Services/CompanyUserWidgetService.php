<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Carbon\Carbon;
use Modules\CompanyUser\Presenters\WidgetCompanyUserProfilePresenter;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyUserWidgetService
{
    public function __construct(
        private EmploymentContractRepository $employmentContractRepository,
        private UserSalaryRepository $userSalaryRepository,
    ) {
    }

    public function getCompanyStatistics(UuidInterface $companyId, UuidInterface $globalId, string $userId)
    {
        $employmentContract = $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);
        $userSalary = $this->userSalaryRepository->getUserSalary($companyId, $globalId);

        return new WidgetCompanyUserProfilePresenter(
            $employmentContract,
            $userSalary,
            $this->getCurrentMonthTaskSummary((string) $companyId, $userId),
        );
    }

    private function getCurrentMonthTaskSummary(string $companyId, string $userId): array
    {
        $timezone = $this->resolveTimezone();
        $now = Carbon::now($timezone);
        $fromDate = $now->copy()->startOfMonth()->toDateString();
        $toDate = $now->copy()->endOfMonth()->toDateString();

        $counts = EmployeeTaskRequest::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereBetween('task_date', [$fromDate, $toDate])
            ->selectRaw(
                '
                COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS accepted_count
                ',
                [EmployeeTaskStatus::Completed->value],
            )
            ->first();

        $totalCount = (int) ($counts?->total_count ?? 0);
        $acceptedCount = (int) ($counts?->accepted_count ?? 0);

        return [
            'period' => 'current_month',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_count' => $totalCount,
            'accepted_count' => $acceptedCount,
            'accepted_status' => EmployeeTaskStatus::Completed->value,
            'accepted_percentage' => $totalCount === 0
                ? 0.0
                : round(($acceptedCount / $totalCount) * 100, 1),
        ];
    }

    private function resolveTimezone(): string
    {
        $timezone = function_exists('getTimeZoneBranchByRequest')
            ? getTimeZoneBranchByRequest()
            : null;

        return $timezone ?: config('app.timezone', 'UTC');
    }
}
