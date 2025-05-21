<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use Modules\CompanyUser\Services\Employee\EmployeeCRUDService;

class SubEntityRecordsService
{
     protected $mappedRegistrationForms = [
        CompanyUserRole::BROKER->value => 'getBrokers',
        CompanyUserRole::EMPLOYEE->value => 'getEmployees',
    ];


    public function __construct(
        protected  SuperEntityService $superEntityService,
        protected SubEntityCRUDService $subEntityCRUDService,
        protected BrokerCRUDService $brokerCRUDService,
        protected EmployeeCRUDService $employeeCRUDService,
        protected RegistrationFormCRUDService $registrationFormCRUDService
    ) {
    }


   public function getRecords(string $subEntityId, string $registrationFormId, int $page = 1, int $perPage = 10): array|Collection|LengthAwarePaginator
    {
        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if(array_key_exists($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->{$this->mappedRegistrationForms[$registrationForm->company_user_role_map]}($page, $perPage);
        }

        //get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        //get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);

        return $model::where('registration_form_id', $registrationFormId)->paginate($perPage);
    }

    protected function getSuperEntityModel(string $superEntityId): string {
        return $this->superEntityService->getModelForId($superEntityId);
    }

    protected function getBrokers(int $page = 1, int $perPage = 10): array
    {
        return $this->brokerCRUDService->listAsSubEntity($page, $perPage);
    }

    protected function getEmployees(int $page = 1, int $perPage = 10): array
    {
        return $this->employeeCRUDService->listAsSubEntity($page, $perPage);
    }
}
