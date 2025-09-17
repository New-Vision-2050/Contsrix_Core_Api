<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Exports;

use App\Exports\BaseExport;
use Modules\Shared\Payment\Services\PaymentCRUDService;

class PaymentExport extends BaseExport
{
    public function __construct(
         PaymentCRUDService $paymentService,
         array $filters = []
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->paymentService->getForExport($this->filters);
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
