<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Warehous\DTO\CreateWarehousDTO;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Modules\Ecommerce\Warehous\Repositories\WarehousRepository;
use Modules\Ecommerce\Warehous\Exports\WarehousExport;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\UuidInterface;

class WarehousCRUDService
{
    public function __construct(
        private WarehousRepository $repository,
    ) {
    }

    public function create(CreateWarehousDTO $createWarehousDTO): Warehous
    {
         return $this->repository->createWarehous($createWarehousDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Warehous
    {
        return $this->repository->getWarehous(
            id: $id,
        );
    }

    /**
     * Get warehouse statistics for dashboard cards
     */
    public function getWarehouseStatistics(): array
    {
            // Get total warehouses count
            $totalWarehouses = Warehous::count();
            // Get active warehouses (assuming status field exists)
            $activeWarehouses = Warehous::where('is_active',1)->count();
            // Get warehouses with low stock (assuming we have products relationship)
            $lowStockWarehouses = Warehous::whereHas('products', function($query) {
                $query->where('stock', '<', 10);
            })->count();
            // Get warehouses created this month
            $newWarehouses = Warehous::whereMonth('created_at', now()->month)->count();
           
            return [
                'total_warehouses' => [
                    'value' => $totalWarehouses,
                    'label' => 'إجمالي عدد المخازن',

                ],
                'active_warehouses' => [
                    'value' => $activeWarehouses,
                    'label' => 'المخازن النشطة',

                ],
                'low_stock_warehouses' => [
                    'value' => $lowStockWarehouses,
                    'label' => 'مخازن المخزون المنخفض',

                ],
                'new_warehouses' => [
                    'value' => $newWarehouses,
                    'label' => 'المخازن الجديدة',

                ]
            ];
     
    }

    /**
     * Export warehouses to Excel
     */
    public function exportToExcel(array $warehouseIds = null, array $filters = []): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Warehous::with([
            'company', 
            'country', 
            'city',
            'products'
        ])->withCount('products');

        // Apply filters
        if ($warehouseIds) {
            $query->whereIn('id', $warehouseIds);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (isset($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', (bool) $filters['is_default']);
        }

        if (isset($filters['district'])) {
            $query->where('district', 'like', '%' . $filters['district'] . '%');
        }

        if (isset($filters['street'])) {
            $query->where('street', 'like', '%' . $filters['street'] . '%');
        }

        if (isset($filters['has_products'])) {
            if ($filters['has_products']) {
                $query->whereHas('products');
            } else {
                $query->whereDoesntHave('products');
            }
        }

        if (isset($filters['min_products_count'])) {
            $query->having('products_count', '>=', $filters['min_products_count']);
        }

        if (isset($filters['max_products_count'])) {
            $query->having('products_count', '<=', $filters['max_products_count']);
        }

        if (isset($filters['latitude_from'])) {
            $query->where('latitude', '>=', $filters['latitude_from']);
        }

        if (isset($filters['latitude_to'])) {
            $query->where('latitude', '<=', $filters['latitude_to']);
        }

        if (isset($filters['longitude_from'])) {
            $query->where('longitude', '>=', $filters['longitude_from']);
        }

        if (isset($filters['longitude_to'])) {
            $query->where('longitude', '<=', $filters['longitude_to']);
        }

        if (isset($filters['near_location'])) {
            $lat = $filters['near_location']['latitude'];
            $lng = $filters['near_location']['longitude'];
            $radius = $filters['near_location']['radius'] ?? 10;
            
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) <= ?
            ", [$lat, $lng, $lat, $radius]);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        $warehouses = $query->get();

        $filename = 'warehouses_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new WarehousExport($warehouses),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Export warehouses to CSV
     */
    public function exportToCsv(array $warehouseIds = null, array $filters = []): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Warehous::with([
            'company', 
            'country', 
            'city',
            'products'
        ])->withCount('products');

        // Apply same filters as Excel export
        if ($warehouseIds) {
            $query->whereIn('id', $warehouseIds);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (isset($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', (bool) $filters['is_default']);
        }

        if (isset($filters['district'])) {
            $query->where('district', 'like', '%' . $filters['district'] . '%');
        }

        if (isset($filters['street'])) {
            $query->where('street', 'like', '%' . $filters['street'] . '%');
        }

        if (isset($filters['has_products'])) {
            if ($filters['has_products']) {
                $query->whereHas('products');
            } else {
                $query->whereDoesntHave('products');
            }
        }

        if (isset($filters['min_products_count'])) {
            $query->having('products_count', '>=', $filters['min_products_count']);
        }

        if (isset($filters['max_products_count'])) {
            $query->having('products_count', '<=', $filters['max_products_count']);
        }

        if (isset($filters['latitude_from'])) {
            $query->where('latitude', '>=', $filters['latitude_from']);
        }

        if (isset($filters['latitude_to'])) {
            $query->where('latitude', '<=', $filters['latitude_to']);
        }

        if (isset($filters['longitude_from'])) {
            $query->where('longitude', '>=', $filters['longitude_from']);
        }

        if (isset($filters['longitude_to'])) {
            $query->where('longitude', '<=', $filters['longitude_to']);
        }

        if (isset($filters['near_location'])) {
            $lat = $filters['near_location']['latitude'];
            $lng = $filters['near_location']['longitude'];
            $radius = $filters['near_location']['radius'] ?? 10;
            
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) <= ?
            ", [$lat, $lng, $lat, $radius]);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        $warehouses = $query->get();

        $filename = 'warehouses_' . now()->format('Y_m_d_H_i_s') . '.csv';

        return Excel::download(
            new WarehousExport($warehouses),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
