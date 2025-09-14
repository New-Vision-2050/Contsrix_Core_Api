<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\EcoAddress\Services\EcoAddressCRUDService;

class EcoAddressExport extends BaseExport
{
    public function __construct(
         EcoAddressCRUDService $ecoaddressService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->ecoaddressService->getForExport($this->filters);
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
