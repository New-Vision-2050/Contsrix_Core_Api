<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class SubEntityRecordsService
{
     protected $mappedRegistrationForms = [
        CompanyUserRole::BROKER->value,
        CompanyUserRole::EMPLOYEE->value,
        CompanyUserRole::CLIENT->value,
    ];


    public function __construct(
        protected  SuperEntityService $superEntityService,
        protected SubEntityCRUDService $subEntityCRUDService,
        protected CompanyUserRepository $companyUserRepository,
        protected RegistrationFormCRUDService $registrationFormCRUDService
    ) {
    }


   public function getRecords(string $subEntityId, string $registrationFormId,int $branchId = null, int $page = 1, int $perPage = 10): array|Collection|LengthAwarePaginator
    {
        $registrationForm = $this->registrationFormCRUDService->getById($registrationFormId);

        if(in_array($registrationForm->company_user_role_map, $this->mappedRegistrationForms)) {
            return $this->getMappedRecords($page, $perPage, $registrationForm->company_user_role_map,$branchId);
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

    protected function getMappedRecords(int $page = 1, int $perPage = 10, $type,$branchId = null): array
    {
        return $this->companyUserRepository->withRelationsFilterByType([], $page, $perPage, $type,null,$branchId);
    }
}
