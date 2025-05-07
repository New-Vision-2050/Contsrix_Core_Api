<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\User\Models\User;
use Illuminate\Support\Collection;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $users;

    public function __construct($users = null)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users ?? User::with([
            'company',
            'companyUser',
            'roles',
            'permissions'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone Code',
            'Phone',
            'Company',
            'Roles',
            'Created At',
            'Last Updated'
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone_code,
            $user->phone,
            $user->company?->name ?? '',
            $user->roles->pluck('name')->implode(', '),
            //$user->permissions->pluck('name')->implode(', '),
            // $user->created_at?->format('Y-m-d H:i:s'),
            // $user->updated_at?->format('Y-m-d H:i:s')
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
