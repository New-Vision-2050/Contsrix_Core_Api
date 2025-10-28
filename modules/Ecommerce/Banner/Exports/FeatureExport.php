<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Exports;

use Modules\Ecommerce\Banner\Services\FeatureCRUDService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class FeatureExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private FeatureCRUDService $featureService,
        private array $filters = []
    ) {
    }

    public function collection(): Collection
    {
        // Get all features for export (you might want to add filtering logic here)
        $features = $this->featureService->getActiveFeatures();
        return collect($features);
    }

    public function headings(): array
    {
        return [
            'ID',
            'العنوان',
            'الوصف', 
            'الحالة',
            'تاريخ الإنشاء',
            'تاريخ التحديث',
        ];
    }

    public function map($feature): array
    {
        return [
            $feature['id'] ?? '',
            $feature['title'] ?? '',
            $feature['description'] ?? '',
            $feature['is_active'] ? 'نشط' : 'غير نشط',
            $feature['created_at'] ?? '',
            $feature['updated_at'] ?? '',
        ];
    }
}
