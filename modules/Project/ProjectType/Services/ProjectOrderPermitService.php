<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

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

    public function list(string $projectId): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->with(['orderPermit', 'department', 'contractor', 'state'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listAll(): Collection
    {
        $projectIds = ProjectManagement::query()->pluck('id');

        return ProjectOrderPermit::query()
            ->whereIn('project_id', $projectIds)
            ->with(['orderPermit', 'department', 'contractor', 'state'])
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function show(string $projectId, string $id): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit', 'department', 'contractor', 'state'])
            ->firstOrFail();
    }


    public function update(string $projectId, string $id, array $data): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        $orderPermit->update([
            'order_permit_id' => Arr::get($data, 'order_permit_id', $orderPermit->order_permit_id),
            'order_permit_department_id' => Arr::get($data, 'order_permit_department_id', $orderPermit->order_permit_department_id),
            'contractor_id' => Arr::get($data, 'contractor_id', $orderPermit->contractor_id),
            'name' => Arr::get($data, 'name', $orderPermit->name),
            'type' => Arr::get($data, 'type', $orderPermit->type),
            'assigned_date' => Arr::get($data, 'assigned_date', $orderPermit->assigned_date),
            'state_id' => Arr::get($data, 'state_id', $orderPermit->state_id),
            'lat' => Arr::get($data, 'lat', $orderPermit->lat),
            'long' => Arr::get($data, 'long', $orderPermit->long),
            'price' => Arr::get($data, 'price', $orderPermit->price),
        ]);

        return $orderPermit->fresh(['orderPermit', 'department', 'contractor', 'state']);
    }


    public function delete(string $projectId, string $id): bool
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $orderPermit->delete();
    }
}
