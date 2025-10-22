<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\Coupon\Services\CouponCRUDService;

class CouponExport extends BaseExport
{
    public function __construct(
         CouponCRUDService $couponService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->couponService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Created At',
            'Updated At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'name'
        ];
    }
}
