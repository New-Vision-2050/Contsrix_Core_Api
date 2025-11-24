<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Exports;

use App\Exports\BaseExport;
use Modules\WebsiteCMS\WebsiteAddress\Services\WebsiteAddressCRUDService;

class WebsiteAddressExport extends BaseExport
{
    public function __construct(
         WebsiteAddressCRUDService $websiteaddressService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->websiteaddressService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'ID',
            'City',
            'Title (AR)',
            'Title (EN)',
            'Latitude',
            'Longitude',
            'Status',
            'Created At',
            'Updated At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->city?->name ?? '',
            $row->getTranslation('title', 'ar'),
            $row->getTranslation('title', 'en'),
            $row->latitude,
            $row->longitude,
            $row->status == 1 ? 'Active' : 'Inactive',
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'title',
            'city_id',
            'status'
        ];
    }
}
