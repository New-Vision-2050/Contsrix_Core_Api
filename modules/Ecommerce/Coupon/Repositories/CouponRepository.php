<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Coupon\Models\Coupon;
use App\Traits\HasExport;

/**
 * @property Coupon $model
 * @method Coupon findOneOrFail($id)
 * @method Coupon findOneByOrFail(array $data)
 */
class CouponRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function getCouponList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCoupon(UuidInterface $id): Coupon
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCoupon(array $data): Coupon
    {
        return $this->create($data);
    }

    public function updateCoupon(UuidInterface $id, array $data): Coupon
    {
        $this->update($id, $data);
        return $this->getCoupon($id);
    }

    public function deleteCoupon(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
