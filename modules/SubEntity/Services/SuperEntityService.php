<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\SubEntity\Repositories\SuperEntityRepository;

class SuperEntityService
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function list(?string $search = ''): array
    {
        return $this->repository->list($search);
    }

    public function getAvailableAttributes(string $superEntityId): array
    {
        return array_map(function($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $this->repository->getAvailableAttributes($superEntityId) ?? []);
    }

    public function getIds()
    {
        return $this->repository->getIds();
    }

    public function getModelForId(string $id): ?string
    {
        return $this->repository->getModelForId($id);
    }

    public function getById(string $id): ?array
    {
        return $this->repository->getById($id);
    }
}
