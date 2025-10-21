<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Handlers;

use Modules\Ecommerce\Coupon\Commands\UpdateCouponCommand;
use Modules\Ecommerce\Coupon\Repositories\CouponRepository;

class UpdateCouponHandler
{
    public function __construct(
        private CouponRepository $repository,
    ) {
    }

    public function handle(UpdateCouponCommand $updateCouponCommand)
    {
        $this->repository->updateCoupon($updateCouponCommand->id, $updateCouponCommand->toArray());
    }
}
