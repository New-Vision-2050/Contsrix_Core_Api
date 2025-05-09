<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use Illuminate\Database\Eloquent\Model;

class SuperEntityRepository
{
    public function __construct(protected array $availableSuperEntities = [])
    {
        $this->availableSuperEntities = config('SubEntity::config.available_super_entities', []);
    }

    public function list(): array
    {
        return collect($this->availableSuperEntities)
            ->select('id', 'name')
            ->toArray();
    }

    public function getAvailableAttributes(string $superEntityId): array
    {
        $entity = collect($this->availableSuperEntities)->firstWhere('id', $superEntityId);

        if (!$entity) {
            abort(404, "Super entity '{$superEntityId}' not found.");
        }

        $modelClass = $entity['model'];

        if (!class_exists($modelClass)) {
            abort(500, "Model class {$modelClass} does not exist.");
        }

        if (!method_exists($modelClass, 'getSubEntitiesAvailableAttributes')) {
            abort(500, "Model {$modelClass} must implement static method getSubEntitiesAvailableAttributes.");
        }

        return $modelClass::getSubEntitiesAvailableAttributes();
    }

    public function getIds(): array
    {
        return collect($this->availableSuperEntities)
            ->pluck('id')
            ->toArray();
    }

    public function getModelForId(string $id): ?string
    {
        return collect($this->availableSuperEntities)
            ->where('id', $id)
            ->pluck('model')
            ->first();
    }

    public function getById(string $id): ?array
    {
        return collect($this->availableSuperEntities)
            ->where('id', $id)
            ->select('id', 'name')
            ->first();
    }
}
