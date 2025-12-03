<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Exports;

use App\Exports\BaseExport;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Services\CategoryWebsiteCMSCRUDService;

class CategoryWebsiteCMSExport extends BaseExport
{
    public function __construct(
         protected CategoryWebsiteCMSCRUDService $categorywebsitecmsService,
         array $filters = []
    ) {
        parent::__construct($categorywebsitecmsService, $filters);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->categorywebsitecmsService->getForExport($this->filters);
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
