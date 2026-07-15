<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusTypeKey;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationSiteStatusTypeKeyRepository;

class ProjectNotificationSiteStatusTypeKeyService
{
    public function __construct(
        private readonly ProjectNotificationSiteStatusTypeKeyRepository $repository,
    ) {}

    public function listByType(string $siteStatusTypeId): Collection
    {
        return $this->repository->findBySiteStatusTypeId($siteStatusTypeId);
    }

    public function listActiveByType(string $siteStatusTypeId): Collection
    {
        return $this->repository->findActiveBySiteStatusTypeId($siteStatusTypeId);
    }

    public function create(CreateProjectNotificationSiteStatusTypeKeyDTO $dto): ProjectNotificationSiteStatusTypeKey
    {
        $data = $dto->toArray();
        $data['key'] = $this->resolveUniqueKey($data['key'], $data['name_ar']);

        return $this->repository->create($data);
    }

    public function update(string $id, UpdateProjectNotificationSiteStatusTypeKeyDTO $dto): ProjectNotificationSiteStatusTypeKey
    {
        $key = $this->repository->findOneOrFail($id);
        $data = $dto->toArray();

        if (! empty($data['key'])) {
            $data['key'] = $this->resolveUniqueKey($data['key'], $data['name_ar'] ?? $key->name_ar, $id);
        }

        $key->update($data);

        return $key->fresh();
    }

    public function delete(string $id): bool
    {
        $key = $this->repository->findOneOrFail($id);

        return $key->delete();
    }

    private function resolveUniqueKey(?string $providedKey, string $nameAr, ?string $excludeId = null): string
    {
        $key = $providedKey;

        if (empty($key)) {
            $key = $this->generateKeyFromName($nameAr);
        }

        $key = (string) preg_replace('/[^a-z0-9_]+/', '_', strtolower($key));
        $key = trim($key, '_');

        if (empty($key)) {
            $key = 'key_' . Str::random(8);
        }

        $originalKey = $key;
        $counter = 1;

        while (
            ProjectNotificationSiteStatusTypeKey::query()
                ->where('key', $key)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $key = "{$originalKey}_{$counter}";
            $counter++;
        }

        return $key;
    }

    private function generateKeyFromName(string $nameAr): string
    {
        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $nameAr);

            if (! empty($transliterated)) {
                return $transliterated;
            }
        }

        return 'key_' . Str::random(8);
    }
}
