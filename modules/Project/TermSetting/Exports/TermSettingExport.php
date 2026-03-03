<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Exports;

use App\Exports\BaseExport;
use Modules\Project\TermSetting\Services\TermSettingCRUDService;

class TermSettingExport extends BaseExport
{
    public function __construct(
        private TermSettingCRUDService $termsettingService,
        protected array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->termsettingService->getForExport($this->filters);
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
