<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubEntityRecordsService
{
    public function __construct(
        protected  SuperEntityService $superEntityService,
        protected SubEntityCRUDService $subEntityCRUDService
    ) {
    }

    public function getRecords(string $subEntityId, string $registrationFormId, int $page = 1, int $perPage = 10): Collection|LengthAwarePaginator
    {
        //get sub_entity
        $sub_entity = $this->subEntityCRUDService->get(Uuid::fromString($subEntityId));
        //get super entity model
        $model = $this->getSuperEntityModel($sub_entity->super_entity);

        return $model::where('registration_form_id', $registrationFormId)->paginate();
    }

    protected function getSuperEntityModel(string $superEntityId): string {
        return $this->superEntityService->getModelForId($superEntityId);
    }
}
