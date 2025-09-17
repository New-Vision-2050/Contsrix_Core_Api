<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Carbon\Carbon;

class SubEntityRecordsService
{
    protected $mappedRegistrationForms = [
        CompanyUserRole::BROKER->value,
        CompanyUserRole::EMPLOYEE->value,
        CompanyUserRole::CLIENT->value,
    ];


    public function __construct(
        protected SuperEntityService          $superEntityService,
        protected SubEntityCRUDService        $subEntityCRUDService,
        protected CompanyUserRepository       $companyUserRepository,
        protected RegistrationFormCRUDService $registrationFormCRUDService
    )
    {
    }


    public function getRecords(string $subEntityId, string $registrationFormId, int $page = 1, int $perPage = 10): array|Collection|LengthAwarePaginator
    {
        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if (in_array($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->getMappedRecords($page, $perPage, $registrationForm->company_user_role_map);
        }

        //get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        //get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);

        return $model::where('registration_form_id', $registrationFormId)->paginate($perPage);
    }

    protected function getSuperEntityModel(string $superEntityId): string
    {
        return $this->superEntityService->getModelForId($superEntityId);
    }

    protected function getMappedRecords( $page = 1,  $perPage = 10, $type,$branchId = null): array    {
        return $this->companyUserRepository->withRelationsFilterByType([], $page, $perPage, $type,null,$branchId);
    }

    public function getWidgetsData(string $subEntityId, string $registrationFormId): array
    {
        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if (in_array($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->getMappedRecordsWidgets($registrationForm->company_user_role_map);
        }

        //get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        //get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);

        return $this->getCustomEntityWidgets($model, $registrationFormId);
    }

    protected function getMappedRecordsWidgets($type): array
    {
        // Get filtered query using same logic as the original method but without pagination
        $query = $this->companyUserRepository->getModel();

        if (method_exists($query, 'scopeFilter')) {
            $query = $query->filter(request()->all());
        }

        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("companies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type);
            });
        });

        // Get current period data
        $totalRecords = $query->count();
        $activeRecords = (clone $query)->whereHas("companies", function ($q) {
            $q->where("company_users_companies.status", 1);
        })->count();
        $suspendedRecords = (clone $query)->whereHas("companies", function ($q) {
            $q->where("company_users_companies.status", 0);
        })->count();

        // Get last month data for comparison
        $lastMonth = Carbon::now();
        $recordsAddedLastMonth = (clone $query)->where('created_at', '>=', $lastMonth->startOfMonth())
            ->where('created_at', '<=', $lastMonth->endOfMonth())
            ->count();

        // Get previous month data for percentage calculation
        $prevMonth = Carbon::now()->subMonth();
        $totalRecordsPrevMonth = (clone $query)->where('created_at', '<=', $prevMonth->endOfMonth())->count();
        $activeRecordsPrevMonth = (clone $query)->whereHas("companies", function ($q) {
            $q->where("company_users_companies.status", 1);
        })->where('created_at', '<=', $prevMonth->endOfMonth())->count();

        if(CompanyUserRole::BROKER->value == $type) {
            $type = "الوسطاء";
        }
        elseif(CompanyUserRole::EMPLOYEE->value == $type) {
            $type = "الموظفين";
        }else{
            $type="العملاء";
        }


        return [
            [
                "title" => " احمالي عدد$type",
                'total' => $totalRecords,
                'percentage' => 100,
            ],
            [
                "title" => "$type المضافين اخر الشهر ",
                'count' => $recordsAddedLastMonth,
                'percentage' =>  $this->calculatePercentageChange($recordsAddedLastMonth, $totalRecords) // No comparison for this metric
            ],
            [
                "title" => "$type النشيطين ",
                'total' => $activeRecords,
                'percentage' => $this->calculatePercentageChange($activeRecords, $totalRecords)
            ],
           [
                "title" => "$type المعلقين ",
                'count' => $suspendedRecords,
                'percentage' => $this->calculatePercentageChange($suspendedRecords, $totalRecords) // Could add comparison if needed
            ]
        ];
    }

    protected function getCustomEntityWidgets(string $model, string $registrationFormId): array
    {
        $query = $model::where('registration_form_id', $registrationFormId);

        // Get current period data
        $totalRecords = $query->count();
        $activeRecords = (clone $query)->where('status', 1)->count();
        $suspendedRecords = (clone $query)->where('status', -1)->count();

        // Get last month data
        $lastMonth = Carbon::now();
        $recordsAddedLastMonth = (clone $query)->where('created_at', '>=', $lastMonth->startOfMonth())
            ->where('created_at', '<=', $lastMonth->endOfMonth())
            ->count();

        // Get previous month data for percentage calculation
        $prevMonth = Carbon::now()->subMonth();
        $totalRecordsPrevMonth = (clone $query)->where('created_at', '<=', $prevMonth->endOfMonth())->count();
        $activeRecordsPrevMonth = (clone $query)->where('status', 1)
            ->where('created_at', '<=', $prevMonth->endOfMonth())
            ->count();

        return [
            'total_records' => [
                'count' => $totalRecords,
                'percentage_change' => $this->calculatePercentageChange($totalRecords, $totalRecordsPrevMonth)
            ],
            'records_added_last_month' => [
                'count' => $recordsAddedLastMonth,
                'percentage_change' => $this->calculatePercentageChange($recordsAddedLastMonth ,$totalRecords)
            ],
            'active_records' => [
                'count' => $activeRecords,
                'percentage_change' => $this->calculatePercentageChange($activeRecords, $totalRecords)
            ],
            'suspended_records' => [
                'count' => $suspendedRecords,
                'percentage_change' => 0
            ]
        ];
    }

    protected function calculatePercentageChange(int $current, int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current / $previous) * 100, 2);
    }
}
