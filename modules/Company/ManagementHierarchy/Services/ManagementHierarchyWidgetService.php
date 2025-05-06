<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\User\Repositories\UserRepository;

class ManagementHierarchyWidgetService
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Get user counts statistics
     *
     * @return array
     */
    public function getUserCountStatistics(): array
    {
        $cacheKey = 'user_count_statistics_' . tenant("id");
        $cacheTtl = 60 * 30; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $companyId = tenant("id");
            return $this->userRepository->getUserCountStatistics($companyId);
        });
    }
    
    /**
     * Get branch counts statistics
     *
     * @return array
     */
    public function getBranchCountStatistics(): array
    {
        $cacheKey = 'branch_count_statistics_' . tenant("id");
        $cacheTtl = 60 * 30; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $companyId = tenant("id");
            return $this->repository->getHierarchyCountStatistics('branch', $companyId);
        });
    }
    
    /**
     * Get management counts statistics
     *
     * @return array
     */
    public function getManagementCountStatistics(): array
    {
        $cacheKey = 'management_count_statistics_' . tenant("id");
        $cacheTtl = 60 * 30; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $companyId = tenant("id");
            return $this->repository->getHierarchyCountStatistics('management', $companyId);
        });
    }
    
    /**
     * Get department counts statistics
     *
     * @return array
     */
    public function getDepartmentCountStatistics(): array
    {
        $cacheKey = 'department_count_statistics_' . tenant("id");
        $cacheTtl = 60 * 30; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $companyId = tenant("id");
            return $this->repository->getHierarchyCountStatistics('department', $companyId);
        });
    }

    /**
     * Get all widgets statistics in one call
     *
     * @return array
     */
    public function getAllWidgetsStatistics(): array
    {
        $cacheKey = 'all_widgets_statistics_' . tenant("id");
        $cacheTtl = 60 * 30; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $companyId = tenant("id");
            
            return [
                'users' => $this->userRepository->getUserCountStatistics($companyId),
                'branches' => $this->repository->getHierarchyCountStatistics('branch', $companyId),
                'management' => $this->repository->getHierarchyCountStatistics('management', $companyId),
                'departments' => $this->repository->getHierarchyCountStatistics('department', $companyId)
            ];
        });
    }
}
