<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class ProjectContractorService
{
    public function create(string $projectId, array $data, ?UploadedFile $logo = null): ProjectContractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $this->assertUniqueReference($projectId, $data['project_contractor_id'] ?? null);

        $projectContractor = new ProjectContractor(array_merge($data, [
            'project_id' => $project->id,
            'company_id' => $project->company_id,
        ]));

        $projectContractor->save();

        if ($logo) {
            $projectContractor->addMedia($logo)->toMediaCollection('logo');
        }

        $this->syncRepresentatives($projectContractor, Arr::get($data, 'representatives', []));

        return $projectContractor->fresh(['country', 'representatives']);
    }

    public function show(string $projectId, string $id): ProjectContractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectContractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['country', 'representatives'])
            ->firstOrFail();
    }

    public function update(string $projectId, string $id, array $data, ?UploadedFile $logo = null): ProjectContractor
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $projectContractor = ProjectContractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!empty($data['project_contractor_id']) && $data['project_contractor_id'] !== $projectContractor->project_contractor_id) {
            $this->assertUniqueReference($projectId, $data['project_contractor_id']);
        }

        $projectContractor->fill($data);
        $projectContractor->save();

        if ($logo) {
            $projectContractor->clearMediaCollection('logo');
            $projectContractor->addMedia($logo)->toMediaCollection('logo');
        }

        $this->syncRepresentatives($projectContractor, Arr::get($data, 'representatives', []));

        return $projectContractor->fresh(['country', 'representatives']);
    }

    public function delete(string $projectId, string $id): bool
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $projectContractor = ProjectContractor::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $projectContractor->delete();
    }

    private function syncRepresentatives(ProjectContractor $projectContractor, array $representatives): void
    {
        $projectContractor->representatives()->delete();

        foreach ($representatives as $representative) {
            if (empty($representative['name'])) {
                continue;
            }

            $projectContractor->representatives()->create([
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

        $exists = ProjectContractor::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('project_contractor_id', $reference)
            ->exists();

        if ($exists) {
            throw new \Exception('Project contractor reference already exists for this project', 422);
        }
    }
}
