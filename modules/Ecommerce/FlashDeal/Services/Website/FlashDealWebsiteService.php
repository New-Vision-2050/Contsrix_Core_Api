<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Services\Website;

use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use Modules\Ecommerce\FlashDeal\Repositories\FlashDealRepository;
use Ramsey\Uuid\UuidInterface;
use Carbon\Carbon;

class FlashDealWebsiteService
{
    public function __construct(
        private FlashDealRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        $now = Carbon::now();
        $query = FlashDeal::query()
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);

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

    public function get(UuidInterface $id): FlashDeal
    {
        $flashDeal = $this->repository->getFlashDeal($id);
        
        // Check if deal is active and current
        $now = Carbon::now();
        if (!$flashDeal->is_active || 
            $flashDeal->start_date > $now || 
            $flashDeal->end_date < $now) {
            abort(404, 'Flash deal not found');
        }
        
        return $flashDeal;
    }
}

