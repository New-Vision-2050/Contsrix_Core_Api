<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Services\Website;

use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use Modules\Ecommerce\FeatureDeal\Repositories\FeatureDealRepository;
use Ramsey\Uuid\UuidInterface;
use Carbon\Carbon;

class FeatureDealWebsiteService
{
    public function __construct(
        private FeatureDealRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        $query = FeatureDeal::query()
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::today())
            ->where('end_date', '>=', Carbon::today());

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->orderBy('created_at', 'desc')
            ->get();

        $lastPage = (int) ceil($total / $perPage);
        $nextPage = $page < $lastPage ? $page + 1 : $lastPage;
        $resultCount = $items->count();

        return [
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'next_page' => $nextPage,
                'last_page' => $lastPage,
                'result_count' => $resultCount,
            ]
        ];
    }

    public function get(UuidInterface $id): FeatureDeal
    {
        $featureDeal = $this->repository->getFeatureDeal($id);
        
        // Check if deal is active and current
        $today = Carbon::today();
        if (!$featureDeal->is_active || 
            $featureDeal->start_date > $today || 
            $featureDeal->end_date < $today) {
            abort(404, 'Feature deal not found');
        }
        
        return $featureDeal;
    }
}

