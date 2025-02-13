<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyUserWidgetsService
{





    public function __construct(
        private CompanyUserRepository $repository,
    )
    {

    }

    public function getTotalUserWidget()//widget number 1
    {
        $totalUserCount =$this->repository->getCompanyUserCount();
        $totalUserCountThisMonth = $this->repository->getCompanyUserCount(Carbon::now());


        return ["total" => $totalUserCount,"percentage"=>$this->calculatePercentage($totalUserCountThisMonth,$totalUserCount)];


    }
    public function getTotalLastMonthUserWidget()//widget number 2
    {
        $totalUserCountThisMonth = $this->repository->getCompanyUserCount(Carbon::now());
        $totalUserCountLastMonth = $this->repository->getCompanyUserCount(Carbon::now());
        return [
            "total" => $totalUserCountThisMonth,
            "percentage"=>$this->calculatePercentageChangeInMonth($totalUserCountThisMonth,$totalUserCountLastMonth)
        ];

    }

    public function getTotalActiveUserWidget ()//widget number 3
    {
        $totalActiveUser=$this->repository->getActiveInactiveCompanyUserCount();
        $totalUserCount =$this->repository->getCompanyUserCount();

        return ["total" => $totalActiveUser,"percentage"=>$this->calculatePercentage($totalActiveUser,$totalUserCount)];

    }

    public function getTotalInactiveUserWidget ()
    {
        $totalInActiveUser=$this->repository->getActiveInactiveCompanyUserCount(status: CompanyUserStatus::INACTIVE->value);
        $totalUserCount =$this->repository->getCompanyUserCount();

        return ["total" => $totalInActiveUser,"percentage"=>$this->calculatePercentage($totalInActiveUser,$totalUserCount)];

    }

    private function calculatePercentageChangeInMonth(int $thisMonth, int $lastMonth): float
    {
        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return (($thisMonth - $lastMonth) / $lastMonth) * 100;
    }

    private function calculatePercentage(int $thisMonth, int $total): float
    {
        if ($total == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return ($thisMonth / $total) * 100;
    }




}
