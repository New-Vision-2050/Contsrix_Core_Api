<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Services\Dashboard;

use Modules\Ecommerce\EcoBrand\DTO\Dashboard\CreateEcoBrandDashboardDTO;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;
use Modules\Ecommerce\EcoBrand\Exports\EcoBrandExport;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\DB;

class EcoBrandCRUDDashboardService
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function create(CreateEcoBrandDashboardDTO $createEcoBrandDTO, $file = null): EcoBrand
    {
         return $this->repository->createEcoBrand($createEcoBrandDTO->toArray(), $file);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getBrandListWithCounts($page, $perPage);
    }

    public function get(UuidInterface $id): EcoBrand
    {
        return $this->repository->getEcoBrand(
            id: $id,
        );
    }

    /**
     * Toggle brand active status
     */
    public function toggleActive(UuidInterface $id): array
    {
        $brand = $this->get($id);
        
        // Toggle the is_active status
        $newStatus = !$brand->is_active;
        $brand->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'نشط' : 'غير مفعل';
        
        return [
            'message' => "تم تغيير حالة العلامة التجارية إلى: {$statusText}",
            'is_active' => $newStatus,
            'status_text' => $statusText,
            'brand' => $brand
        ];
    }

    /**
     * Get brand statistics cards for dashboard
     */
    public function getBrandStatistics(): array
    {
        // Single query to get all statistics (tenant filtering automatic)
        $stats = EcoBrand::selectRaw('
            COUNT(*) as total_brands,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_brands,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_brands
        ')->first();

        // Get total products count (tenant filtering automatic)
        $totalProducts = EcoBrand::withCount('products')->get()->sum('products_count');

        return [
            [
                'number' =>(int) $stats->total_brands,
                'title' => 'إجمالي البرندات',
            ],
            [
                'number' => $stats->active_brands,
                'title' => 'البرندات الفعالة',
            ],
            [
                'number' => $stats->inactive_brands,
                'title' => 'البرندات الغير فعالة',
            ],
            [
                'number' => $totalProducts,
                'title' => 'إجمالي المنتجات',
            ]
        ];
    }

    /**
     * Export brands to Excel
     */
    public function exportToExcel(?array $brandIds = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $fileName = 'eco_brands_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        
        // Get brands based on IDs or all brands (tenant filtering automatic)
        $brands = null;
        if ($brandIds && !empty($brandIds)) {
            $brands = EcoBrand::whereIn('id', $brandIds)->get();
        }
        // If no brandIds provided, EcoBrandExport will get all brands automatically
        
        $response = Excel::download(new EcoBrandExport($brands), $fileName);
        
        return $response;
    }
}
