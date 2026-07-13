<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class ProjectOrderPermitService
{
    public function createMany(array $data): array
    {
        $projectId = (string) Arr::get($data, 'project_id');
        $items = [];

        foreach (Arr::get($data, 'work_orders', []) as $workOrderData) {
            $items[] = ProjectOrderPermit::query()->create([
                'project_id' => $projectId,
                'order_permit_id' => Arr::get($workOrderData, 'order_permit_id'),
                'order_permit_department_id' => Arr::get($workOrderData, 'order_permit_department_id'),
                'contractor_id' => Arr::get($workOrderData, 'contractor_id'),
                'name' => Arr::get($workOrderData, 'name'),
                'type' => Arr::get($workOrderData, 'type'),
                'assigned_date' => Arr::get($workOrderData, 'assigned_date'),
                'state_id' => Arr::get($workOrderData, 'state_id'),
                'lat' => Arr::get($workOrderData, 'lat'),
                'long' => Arr::get($workOrderData, 'long'),
                'price' => Arr::get($workOrderData, 'price'),
            ]);
        }

        return $items;
    }
}
