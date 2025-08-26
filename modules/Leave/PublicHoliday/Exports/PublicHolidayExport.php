<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Leave\PublicHoliday\Services\PublicHolidayCRUDService;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PublicHolidayExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private PublicHolidayCRUDService $publicHolidayService;
    private array $filters;

    public function __construct(PublicHolidayCRUDService $publicHolidayService, array $filters = [])
    {
        $this->publicHolidayService = $publicHolidayService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->publicHolidayService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Country ID',
            'Country Name',
            'Start Date',
            'End Date',
            'Duration (Days)',
            'Company ID',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param PublicHoliday $publicHoliday
     * @return array
     */
    public function map($publicHoliday): array
    {
        $startDate = \Carbon\Carbon::parse($publicHoliday->date_start);
        $endDate = \Carbon\Carbon::parse($publicHoliday->date_end);
        $duration = $startDate->diffInDays($endDate) + 1;

        return [
            $publicHoliday->id,
            $publicHoliday->name,
            $publicHoliday->country_id,
            $publicHoliday->country?->name ?: '-',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $duration,
            $publicHoliday->company_id,
            $publicHoliday->created_at->format('Y-m-d H:i:s'),
            $publicHoliday->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set RTL direction for the entire sheet to better handle Arabic text
        $sheet->setRightToLeft(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ];
    }
}
