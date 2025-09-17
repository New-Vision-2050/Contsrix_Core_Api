<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\EcoShop\Services\EcoShopCRUDService;

class EcoShopExport extends BaseExport
{
    public function __construct(
         EcoShopCRUDService $ecoshopService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->ecoshopService->getForExport($this->filters);
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
