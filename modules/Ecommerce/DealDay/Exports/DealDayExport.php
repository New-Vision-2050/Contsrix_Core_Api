<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\DealDay\Services\DealDayCRUDService;

class DealDayExport extends BaseExport
{
    public function __construct(
         DealDayCRUDService $dealdayService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->dealdayService->getForExport($this->filters);
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
