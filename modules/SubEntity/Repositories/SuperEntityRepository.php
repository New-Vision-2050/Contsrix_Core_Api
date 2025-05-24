<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\SubEntity\Models\RegistrationForm;
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

    public function getRegistrationFormsForId(string $id): Collection
    {
        $forms = collect($this->availableSuperEntities)
            ->where('id', $id)
            ->pluck('registration_forms')
            ->first();

        return RegistrationForm::whereIn('slug', $forms)->get(['id', 'name', 'slug', 'is_active']);
    }

    public function getById(string $id): ?array
    {
        return collect($this->availableSuperEntities)
            ->where('id', $id)
            ->select('id', 'name')
            ->first();
    }

    public function setAttributesConfig(string $superEntityId, $attributes): array
    {
        $config = ['allowed_attributes' => $attributes];

        $existing = DB::table('super_entities_config')
            ->where('super_entity', $superEntityId)
            ->first();

        if ($existing) {
            $existingConfig = $existing->config;
            if(!isArray($existingConfig)){
                $existingConfig = json_decode($existingConfig,true);
            }
            $existingConfig['allowed_attributes'] = $attributes;

            DB::table('super_entities_config')
                ->where('super_entity', $superEntityId)
                ->update(values: [
                    'config' => json_encode($existingConfig),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('super_entities_config')->insert([
                'id' => Str::uuid(),
                'super_entity' => $superEntityId,
                'config' => json_encode($config),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $config;
    }

    public function getAttributesConfig(string $superEntityId): array
    {
        $config = DB::table('super_entities_config')
            ->where('super_entity', $superEntityId)
            ->value('config');

        if (!$config) {
            return [];
        }

        $decoded = json_decode($config, true);

        return $decoded['allowed_attributes'] ?? [];
    }

    public function setConfigValue(string $superEntityId, string $key, $value): array
    {
        $existing = DB::table('super_entities_config')
            ->where('super_entity', $superEntityId)
            ->first();

        if ($existing) {
            $existingConfig = json_decode($existing->config, true) ?? [];
            $existingConfig[$key] = $value;

            DB::table('super_entities_config')
                ->where('super_entity', $superEntityId)
                ->update([
                    'config' => json_encode($existingConfig),
                    'updated_at' => now(),
                ]);
        } else {
            $newConfig = [$key => $value];

            DB::table('super_entities_config')->insert([
                'id' => Str::uuid(),
                'super_entity' => $superEntityId,
                'config' => json_encode($newConfig),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$key => $value];
    }

    public function getConfigValue(string $superEntityId, string $key)
    {
        $config = DB::table('super_entities_config')
            ->where('super_entity', $superEntityId)
            ->value('config');

        if (!$config) {
            return null;
        }

        $decoded = json_decode($config, true);

        return $decoded[$key] ?? null;
    }
}
