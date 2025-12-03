<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Exports;

use App\Exports\BaseExport;
use Modules\WebsiteCMS\WebsiteService\Services\WebsiteServiceCRUDService;

class WebsiteServiceExport extends BaseExport
{
    public function __construct(
         protected WebsiteServiceCRUDService $websiteServiceService,
         array $filters = []
    ) {
        parent::__construct($websiteServiceService, $filters);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->websiteServiceService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name (AR)',
            'Name (EN)',
            'Reference Number',
            'Category',
            'Description (AR)',
            'Description (EN)',
            'Main Image',
            'Icon',
            'Company ID',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param mixed $service
     * @return array
     */
    public function map($service): array
    {
        return [
            $service->id,
            $service->getTranslation('name', 'ar'),
            $service->getTranslation('name', 'en'),
            $service->reference_number,
            $service->category ? $service->category->getTranslation('name', app()->getLocale()) : '',
            $service->getTranslation('description', 'ar'),
            $service->getTranslation('description', 'en'),
            $service->getFirstMediaUrl('main_image'),
            $service->getFirstMediaUrl('icon'),
            $service->company_id,
            $service->created_at?->toDateTimeString(),
            $service->updated_at?->toDateTimeString(),
        ];
    }
}
