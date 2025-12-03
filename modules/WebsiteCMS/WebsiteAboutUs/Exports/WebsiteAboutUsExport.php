<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Exports;

use App\Exports\BaseExport;
use Modules\WebsiteCMS\WebsiteAboutUs\Services\WebsiteAboutUsCRUDService;

class WebsiteAboutUsExport extends BaseExport
{
    public function __construct(
         WebsiteAboutUsCRUDService $websiteaboutusService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->websiteaboutusService->getForExport($this->filters);
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
