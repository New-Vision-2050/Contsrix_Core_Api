<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Project\ProjectManagement\Models\ProjectProcedureJobAttribute;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;

class ProjectProcedurePresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectProcedureSetting $projectProcedure) {}

    protected function present(bool $isListing = false): array
    {
        $procedureSetting = $this->projectProcedure->procedureSetting;

        return [
            'id' => $procedureSetting?->id ?? $this->projectProcedure->procedure_setting_id,
            'project_procedure_setting_id' => $this->projectProcedure->id,
            'project_id' => $this->projectProcedure->project_id,
            'procedure_setting_id' => $this->projectProcedure->procedure_setting_id,
            'parent_id' => $procedureSetting?->parent_id,
            'name' => $procedureSetting?->name,
            'procedure_name' => $procedureSetting?->name,
            'type' => $procedureSetting?->type,
            'execute_type' => $procedureSetting?->execute_type,
            'icon' => $procedureSetting?->icon,
            'percentage' => $procedureSetting?->percentage,
            'deadline_days' => $procedureSetting?->deadline_days,
            'deadline_hours' => $procedureSetting?->deadline_hours,
            'sort_order' => $procedureSetting?->sort_order,
            'is_active' => (bool) ($procedureSetting?->is_active ?? false),
            'work_flow_id' => $procedureSetting?->work_flow_id,
            'attachment_type' => $this->folderData($this->projectProcedure->attachmentType),
            'attachment_sub_type' => $this->folderData($this->projectProcedure->attachmentSubType),
            'attachment_sub_sub_type' => $this->folderData($this->projectProcedure->attachmentSubSubType),
            'job_attribute' => $this->jobAttributeData($this->projectProcedure->jobAttribute),
            'used_in_document_cycle' => $this->projectProcedure->used_in_document_cycle,
            'appears_in_archive_after_approval' => $this->projectProcedure->appears_in_archive_after_approval,
            'appears_in_attachments_library' => $this->projectProcedure->appears_in_attachments_library,
            'requires_asset_id' => $this->projectProcedure->requires_asset_id,
            'procedure_setting' => $procedureSetting ? [
                'id' => $procedureSetting->id,
                'name' => $procedureSetting->name,
                'type' => $procedureSetting->type,
                'execute_type' => $procedureSetting->execute_type,
                'is_active' => (bool) $procedureSetting->is_active,
                'work_flow_id' => $procedureSetting->work_flow_id,
                'parent_id' => $procedureSetting->parent_id,
            ] : null,
            'created_at' => $this->projectProcedure->created_at?->toDateTimeString(),
            'updated_at' => $this->projectProcedure->updated_at?->toDateTimeString(),
        ];
    }

    private function folderData(?Folder $folder): ?array
    {
        if (! $folder) {
            return null;
        }

        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'project_id' => $folder->project_id,
        ];
    }

    private function jobAttributeData(?ProjectProcedureJobAttribute $jobAttribute): ?array
    {
        if (! $jobAttribute) {
            return null;
        }

        return [
            'id' => $jobAttribute->id,
            'name' => $jobAttribute->name,
            'code' => $jobAttribute->code,
            'is_active' => (bool) $jobAttribute->is_active,
        ];
    }
}
