<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use Illuminate\Support\Str;
use Modules\SubEntity\Models\RegistrationForm;
use Monolog\Registry;

class SuperEntityRepository
{
    public function __construct(protected array $availableSuperEntities = [])
    {
        $this->availableSuperEntities = config('SubEntity::config.available_super_entities', []);
    }

    public function list(?string $search = ''): array
    {
        return collect($this->availableSuperEntities)
            ->when(filled($search), function ($collection) use ($search) {
                return $collection->filter(function ($entity) use ($search) {
                    $entityName = $entity['name'][app()->getLocale()] ?? '';
                    return Str::contains($entityName, $search, true);
                });
            })
            ->values()
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

    public function getRegistrationFormsForId(string $id): array
    {
        $forms = collect($this->availableSuperEntities)
            ->where('id', $id)
            ->pluck('registration_forms')
            ->first();

        $forms = RegistrationForm::whereIn('slug', $forms)->get(['id', 'name', 'slug', 'is_active']);

        return $forms->map(function ($form) {
            $form['name'] = $form['name'][app()->getLocale()];
            return $form;
        })->toArray();
    }

    public function getById(string $id): ?array
    {
        return collect($this->availableSuperEntities)
            ->where('id', $id)
            ->select('id', 'name')
            ->first();
    }
}
