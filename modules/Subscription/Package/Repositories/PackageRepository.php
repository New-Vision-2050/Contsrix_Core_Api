<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Repositories;

use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\Package\Models\Package;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Subscription\Package\Models\PackageFeature;

/**
 * @property Package $model
 * @method Package findOneOrFail($id)
 * @method Package findOneByOrFail(array $data)
 */
class PackageRepository extends BaseRepository
{
    public function __construct(Package $model)
    {
        parent::__construct($model);
    }

    public function getPackageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPackage(UuidInterface $id): Package
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPackage(array $data): Package
    {
        return $this->create($data);
    }

    public function updatePackage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePackage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function upsertFeatures(string $packageId, array $features): void
    {
        $now = now();

        $records = collect($features)
            ->unique('permission_id')
            ->map(function ($feature) use ($packageId, $now) {
                return [
                    'id' => Str::uuid()->toString(),
                    'package_id' => $packageId,
                    'permission_id' => $feature['permission_id'],
                    'limit' => $feature['limit'] ?? null,
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        PackageFeature::upsert(
            $records,
            ['package_id', 'permission_id'],
            ['limit', 'is_enabled', 'updated_at']
        );
    }

}
