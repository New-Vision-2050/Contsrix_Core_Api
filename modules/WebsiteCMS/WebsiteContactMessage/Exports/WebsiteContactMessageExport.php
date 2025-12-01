<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Exports;

use App\Exports\BaseExport;
use Modules\WebsiteCMS\WebsiteContactMessage\Services\WebsiteContactMessageCRUDService;

class WebsiteContactMessageExport extends BaseExport
{
    public function __construct(
         WebsiteContactMessageCRUDService $websitecontactmessageService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->websitecontactmessageService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'Address',
            'Status',
            'Message',
            'Created At',
            'Updated At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->phone,
            $row->email,
            $row->address,
            $row->status == 1 ? 'Active' : 'Inactive',
            $row->message,
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'name',
            'phone',
            'email',
            'status'
        ];
    }
}
