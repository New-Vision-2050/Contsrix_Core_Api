<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Coupon\DTO\CreateCouponDTO;
use Modules\Ecommerce\Coupon\Models\Coupon;
use Modules\Ecommerce\Coupon\Repositories\CouponRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class CouponCRUDService
{
    use HasExportService;

    public function __construct(
        private CouponRepository $repository,
    ) {
    }

    public function create(CreateCouponDTO $createCouponDTO): Coupon
    {
         return $this->repository->createCoupon($createCouponDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Coupon
    {
        return $this->repository->getCoupon(
            id: $id,
        );
    }

    public function toggleStatus(UuidInterface $id): Coupon
    {
        $coupon = $this->repository->getCoupon($id);
        
        // Toggle the is_active status
        $newStatus = !$coupon->is_active;
        
        return $this->repository->updateCoupon($id, ['is_active' => $newStatus]);
    }
}
