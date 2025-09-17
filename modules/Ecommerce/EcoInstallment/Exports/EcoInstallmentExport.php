<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\EcoInstallment\Services\EcoInstallmentCRUDService;

class EcoInstallmentExport extends BaseExport
{
    public function __construct(
         EcoInstallmentCRUDService $ecoinstallmentService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->ecoinstallmentService->getForExport($this->filters);
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
