<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Collection;

abstract class BaseExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $service;
    protected array $filters;

    public function __construct($service, array $filters = [])
    {
        $this->service = $service;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return $this->service->getForExport($this->filters);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
            ],
        ];
    }

    /**
     * Define the headings for the export
     * Must be implemented by child classes
     */
    abstract public function headings(): array;

    /**
     * Map the data for each row
     * Must be implemented by child classes
     */
    abstract public function map($row): array;

    /**
     * Get filterable columns for the model
     * Must be implemented by child classes
     */
    abstract public function getFilterableColumns(): array;
}
