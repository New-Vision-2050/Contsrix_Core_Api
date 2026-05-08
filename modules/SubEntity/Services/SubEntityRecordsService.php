<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
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
    ) {}


    public function getRecords(string $subEntityId, string $registrationFormId, $branchId = null, $page = 1, $perPage = 10): array|Collection|LengthAwarePaginator
    {
        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if (in_array($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->getMappedRecords($page, $perPage, $registrationForm->company_user_role_map, $branchId);
        }

        //get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        //get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);
        $query = $model::query();
        if ($model === User::class) {
            $query->whereHas('companyUserCompanies', function ($q) use ($registrationForm, $subEntityId) {
                $q->where('role', $registrationForm->company_user_role_map)
                    ->where('sub_entity_id', $subEntityId);
            });
        }
        return $query->paginate($perPage);
    }

    protected function getSuperEntityModel(string $superEntityId): string
    {
        return $this->superEntityService->getModelForId($superEntityId);
    }

    protected function getMappedRecords($page = 1, $perPage = 10, $type, $branchId = null): array
    {
        return $this->companyUserRepository->withRelationsFilterByType(
            [
                'users.companyUserCompanies',
                'bankAccount',
                'userProfessionalData.jobTitle',
                'jobOffer',
                'employmentContract',
                'userSalary',
                'userAbout',
                'contactInfo',
                'qualifications.academicQualification',
                'userExperiences',
                'userEducationalCourses',
                'professionalCertificates',
                'userPrivileges.typePrivilege',
                'userRelatives',
                'contractualRelationships',
            ],
            $page,
            $perPage,
            $type,
            null,
            $branchId
        );
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
            $query->whereHas("users.companyUserCompanies", function ($query) use ($type) {
                $query->where("role", $type);
            });
        })->when(request()->has('sub_entity_id'), function ($query) use ($type) {
            $query->whereHas("users.companyUserCompanies", function ($query) use ($type) {
                $query->where("sub_entity_id", request()->sub_entity_id);
            });
        })
            // Only count users who have roles (user relationship exists)
            ->whereHas('users', function ($query) {
                $query->whereNotNull('id');
            });

        $activeStatus   = (string) CompanyUserStatus::ACTIVE->value;   // "1"
        $inactiveStatus = (string) CompanyUserStatus::INACTIVE->value; // "0"
        $roleStr        = (string) $type;

        // Get current period data
        $totalRecords = $query->count();
        $activeRecords = (clone $query)->whereHas("users.companyUserCompanies", function ($q) use ($roleStr, $activeStatus) {
            $q->where("role", $roleStr)->where("status", $activeStatus);
        })->count();
        $suspendedRecords = (clone $query)->whereHas("users.companyUserCompanies", function ($q) use ($roleStr, $inactiveStatus) {
            $q->where("role", $roleStr)->where("status", $inactiveStatus);
        })->count();

        // Get last month data for comparison
        $lastMonth = Carbon::now();
        $recordsAddedLastMonth = (clone $query)->whereDate('created_at', '>=', Carbon::now()->startOfMonth())
            ->whereDate('created_at', '<=', Carbon::now()->endOfMonth())
            ->count();

        // Get previous month data for percentage calculation
        $prevMonth = Carbon::now()->subMonth();
        $totalRecordsPrevMonth = (clone $query)->where('created_at', '<=', $prevMonth->endOfMonth())->count();
        $activeRecordsPrevMonth = (clone $query)->whereHas("users.companyUserCompanies", function ($q) use ($roleStr, $activeStatus) {
            $q->where("role", $roleStr)->where("status", $activeStatus);
        })->where('created_at', '<=', $prevMonth->endOfMonth())->count();

        if (CompanyUserRole::BROKER->value == $type) {
            $typeLabel = __('brokers');
        } elseif (CompanyUserRole::EMPLOYEE->value == $type) {
            $typeLabel = __('employees');
        } else {
            $typeLabel = __('clients');
        }


        return [
            [
                "title" => __('total_count', ['type' => $typeLabel]),
                'total' => $totalRecords,
                'percentage' => 100,
            ],
            [
                "title" => __('added_last_month', ['type' => $typeLabel]),
                'total' => $recordsAddedLastMonth,
                'percentage' => $this->calculatePercentageChange($recordsAddedLastMonth, $totalRecords), // No comparison for this metric
                "start" => Carbon::now()->startOfMonth(),
                "end" => Carbon::now()->endOfMonth()
            ],
            [
                "title" => __('active_records', ['type' => $typeLabel]),
                'total' => $activeRecords,
                'percentage' => $this->calculatePercentageChange($activeRecords, $totalRecords)
            ],
            [
                "title" => __('suspended_records', ['type' => $typeLabel]),
                'total' => $suspendedRecords,
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
                'percentage_change' => $this->calculatePercentageChange($recordsAddedLastMonth, $totalRecords)
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

    /**
     * Get records for export without pagination
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForExport(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $subEntityId = $filters['sub_entity_id'];
        $registrationFormId = $filters['registration_form_id'];
        $branchId = $filters['branch_id'] ?? null;
        $ids = $filters['ids'] ?? null;

        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if (in_array($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->getMappedRecordsForExport($registrationForm->company_user_role_map, $branchId, $ids);
        }

        // Get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        // Get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);
        $query = $model::query();

        if ($model === User::class) {
            $query->whereHas('companyUserCompanies', function ($q) use ($registrationForm, $subEntityId) {
                $q->where('role', $registrationForm->company_user_role_map)
                    ->where('sub_entity_id', $subEntityId);
            });

            // Load relationships for export
            //            $query->with(['companyUserCompanies.branch']);
        }

        // Apply ID filter if provided
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    /**
     * Get mapped records for export without pagination
     *
     * @param int $type
     * @param string|null $branchId
     * @param array|null $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getMappedRecordsForExport(int $type, ?string $branchId = null, ?array $ids = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->companyUserRepository->getModel();

        if (method_exists($query, 'scopeFilter')) {
            $query = $query->filter(request()->all());
        }

        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("users.companyUserCompanies", function ($query) use ($type) {
                $query->where("role", $type);
            });
        });

        // Apply branch filter if provided
        if ($branchId) {
            $query->whereHas("users.companyUserCompanies", function ($query) use ($branchId) {
                $query->where("branch_id", $branchId);
            });
        }

        // Apply ID filter if provided
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        // Load relationships for export
        $query->with(['companies']);

        return $query->get();
    }
}
