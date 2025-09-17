<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Exports;

use App\Exports\BaseExport;
use Modules\Shared\Installment\Services\InstallmentCRUDService;

class InstallmentExport extends BaseExport
{
    public function __construct(
         InstallmentCRUDService $installmentService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->installmentService->getForExport($this->filters);
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
