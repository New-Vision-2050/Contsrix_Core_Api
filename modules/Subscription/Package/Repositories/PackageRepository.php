<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Repositories;

use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\Subscription\Package\Models\Package;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Subscription\Package\DTO\CreatePackageDTO;
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

    public function createPackage(CreatePackageDTO $createPackageDTO): Package
    {
        $package = $this->create($createPackageDTO->toArray());

        // Sync company fields
        if (!empty($createPackageDTO->companyFields)) {
            $package->companyFields()->sync($createPackageDTO->companyFields);
        }

        // Sync company types
        if (!empty($createPackageDTO->companyTypes)) {
            $package->companyTypes()->sync($createPackageDTO->companyTypes);
        }

        // Sync countries
        if (!empty($createPackageDTO->countries)) {
            $package->countries()->sync($createPackageDTO->countries);
        }

        return $package;
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

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model->newQuery();
        }

        // Simple column filters
        if (isset($conditions['is_active'])) {
            $query->where('is_active', $conditions['is_active']);
        }

        if (!empty($conditions['name'])) {
            $query->where('name', 'LIKE', '%' . $conditions['name'] . '%');
        }

        if (!empty($conditions['company_access_program_id'])) {
            $query->where('company_access_program_id',  $conditions['company_access_program_id']);
        }

        // // Relational filter
        if (!empty($conditions['company_field_id'])) {
            $query->whereHas('companyFields', function ($q) use ($conditions) {
                $q->where('company_fields.id', $conditions['company_field_id']);
            });
        }

        $query->withCount(['features']);
        $query->with(['companyFields:id,name', 'companyTypes:id,name', 'countries:id,name,currency,currency_name,currency_symbol']);

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy($orderBy, $sortBy)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function counts(): array
    {
        $counts = $this->model
            ->selectRaw('COUNT(*) as total_packages')
            ->selectRaw('COUNT(CASE WHEN is_active = true THEN 1 END) as active_packages')
            ->selectRaw('COUNT(CASE WHEN is_active = false THEN 1 END) as inactive_packages')
            ->first();

        $totalCompanies = DB::table('company_package')
            ->distinct('company_id')
            ->count('company_id');

        return [
            'total_packages' => (int) $counts->total_packages,
            'active_packages' => (int) $counts->active_packages,
            'inactive_packages' => (int) $counts->inactive_packages,
            'total_companies' => $totalCompanies,
        ];
    }

    public function syncPermissions(Package $package, array $permissionIds, array $limits = []): void
    {
        // Prepare sync data with limits
        $syncData = [];

        foreach ($permissionIds as $permissionId) {
            $syncData[$permissionId] = [
                'limit' => $limits[$permissionId] ?? null
            ];
        }

        $package->permissions()->sync($syncData);
    }

    /**
     * Find packages by IDs with all their permissions.
     */
    public function findByIdsWithPermissions(array $packageIds): Collection
    {
        return $this->model->whereIn('id', $packageIds)
            ->with(['permissions'])
            ->get();
    }

    /**
     * Find a package with permissions and companies.
     */
    public function findWithPermissionsAndCompanies(string $packageId): Package
    {
        return $this->model->with(['permissions', 'companies'])->findOrFail($packageId);
    }
}
