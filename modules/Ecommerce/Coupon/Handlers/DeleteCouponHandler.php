<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Handlers;

use Modules\Ecommerce\Coupon\Repositories\CouponRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCouponHandler
{
    public function __construct(
        private CouponRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCoupon($id);
    }
}
