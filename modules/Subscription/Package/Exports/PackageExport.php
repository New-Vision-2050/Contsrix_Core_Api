<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Subscription\Package\Services\PackageCRUDService;
use Modules\Subscription\Package\Models\Package;

class PackageExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private PackageCRUDService $packageService;
    private array $filters;

    public function __construct(PackageCRUDService $packageService, array $filters = [])
    {
        $this->packageService = $packageService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->packageService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Type',
            'Price',
            'Currency',
            'Duration (Days)',
            'Max Users',
            'Max Branches',
            'Status',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param Package $package
     * @return array
     */
    public function map($package): array
    {
        return [
            $package->id,
            $package->name,
            $package->description,
            $package->type,
            $package->price,
            $package->currency,
            $package->duration_in_days,
            $package->max_users,
            $package->max_branches,
            $package->is_active ? 'Active' : 'Inactive',
            $package->created_at->format('Y-m-d H:i:s'),
            $package->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
