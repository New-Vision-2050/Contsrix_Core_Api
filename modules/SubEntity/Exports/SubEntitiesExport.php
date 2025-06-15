<?php

namespace Modules\SubEntity\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubEntity\Presenters\SubEntityPresenter;

class SubEntitiesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $subEntities;

    public function __construct($subEntities = null)
    {
        $this->subEntities = $subEntities;
    }

    public function collection()
    {
        return $this->subEntities ?? SubEntity::with(['mainProgram', 'registrationForm'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Is Active',
            'Is Registrable',
            'Super Entity Name',
            'Main Program Name',
            'Attributes Count',
            'Usage Count',
            'Created At',
            'Updated At',
        ];
    }

    public function map($subEntity): array
    {
        $presenter = new SubEntityPresenter($subEntity);
        $data = $presenter->getData();

        return [
            $data['id'],
            $data['name'],
            $data['slug'],
            $data['is_active'] ? 'Yes' : 'No',
            $data['is_registrable'] ? 'Yes' : 'No',
            $data['super_entity']['name'] ?? '',
            $data['main_program']['name'] ?? '',
            $data['attributes_count'] ?? 0,
            $data['usage_count'] ?? 0,
            $data['created_at'],
            $data['updated_at'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true); // optional RTL support

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
