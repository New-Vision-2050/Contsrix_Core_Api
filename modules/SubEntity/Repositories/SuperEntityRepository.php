<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

class SuperEntityRepository
{
    public function __construct(protected array $availableSuperEntities = [])
    {
        $this->availableSuperEntities = config('SubEntity::config.available_super_entities', []);
    }

    public function list(): array
    {
        return collect($this->availableSuperEntities)
            ->pluck('name')
            ->toArray();
    }

    public function getAvailableAttributes(string $superEntityName): array
    {
        $entity = collect($this->availableSuperEntities)->firstWhere('name', $superEntityName);

        if (!$entity) {
            abort(404, "Super entity '{$superEntityName}' not found.");
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
}
