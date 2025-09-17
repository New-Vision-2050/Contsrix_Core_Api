<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\EcoBankAccount\Services\EcoBankAccountCRUDService;

class EcoBankAccountExport extends BaseExport
{
    public function __construct(
         EcoBankAccountCRUDService $ecobankaccountService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->ecobankaccountService->getForExport($this->filters);
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
