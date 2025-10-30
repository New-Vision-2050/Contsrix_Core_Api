<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\Home\Services\HomeCRUDService;

class HomeExport extends BaseExport
{
    public function __construct(
         HomeCRUDService $homeService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->homeService->getForExport($this->filters);
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
