<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Modules\Project\ProjectManagement\Models\Contractor;
use Modules\Project\ProjectManagement\Models\ContractorRepresentative;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Support\Arr;

class ContractorService
{
    public function create(string $projectId, array $data, ?UploadedFile $logo = null): Contractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $this->assertUniqueReference($projectId, $data['project_contractor_id'] ?? null);

        $contractor = new Contractor(array_merge($data, [
            'project_id' => $project->id,
            'company_id' => $project->company_id,
        ]));

        $contractor->save();

        if ($logo) {
            $contractor->addMedia($logo)->toMediaCollection('logo');
        }

        $this->syncRepresentatives($contractor, Arr::get($data, 'representatives', []));

        return $contractor->fresh(['country', 'representatives']);
    }

    public function show(string $projectId, string $id): Contractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return Contractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['country', 'representatives'])
            ->firstOrFail();
    }

    public function update(string $projectId, string $id, array $data, ?UploadedFile $logo = null): Contractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $contractor = Contractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!empty($data['project_contractor_id']) && $data['project_contractor_id'] !== $contractor->project_contractor_id) {
            $this->assertUniqueReference($projectId, $data['project_contractor_id']);
        }

        $contractor->fill($data);
        $contractor->save();

        if ($logo) {
            $contractor->clearMediaCollection('logo');
            $contractor->addMedia($logo)->toMediaCollection('logo');
        }

        $this->syncRepresentatives($contractor, Arr::get($data, 'representatives', []));

        return $contractor->fresh(['country', 'representatives']);
    }

    public function delete(string $projectId, string $id): bool
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $contractor = Contractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $contractor->delete();
    }

    private function syncRepresentatives(Contractor $contractor, array $representatives): void
    {
        $contractor->representatives()->delete();

        foreach ($representatives as $representative) {
            if (empty($representative['name'])) {
                continue;
            }

            $contractor->representatives()->create([
                'name' => $representative['name'],
                'mobile' => $representative['mobile'] ?? null,
                'nationality' => $representative['nationality'] ?? null,
            ]);
        }
    }

    private function assertUniqueReference(string $projectId, ?string $reference): void
    {
        if (empty($reference)) {
            return;
        }

        $exists = Contractor::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('project_contractor_id', $reference)
            ->exists();

        if ($exists) {
            throw new \Exception('Contractor reference already exists for this project', 422);
        }
    }
}
