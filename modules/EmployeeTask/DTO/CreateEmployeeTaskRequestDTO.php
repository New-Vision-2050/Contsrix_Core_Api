<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

final class CreateEmployeeTaskRequestDTO
{
    public function __construct(
        public readonly string  $userId,
        public readonly string  $title,
        public readonly string  $employee_task_type_id,
        public readonly string  $itemType,
        public readonly string  $itemId,
        public readonly float   $durationHours,
        public readonly string  $taskDate,
        public readonly float   $taskLatitude,
        public readonly float   $taskLongitude,
        public readonly ?string $description              = null,
        public readonly ?string $projectId                = null,
        public readonly ?string $approvalResponsibleId    = null,
        public readonly ?string $assignmentResponsibleId  = null,
        public readonly ?string $notes                    = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id'                    => $this->userId,
            'title'                      => $this->title,
            'employee_task_type_id'      => $this->employee_task_type_id,
            'item_type'                  => $this->itemType,
            'item_id'                    => $this->itemId,
            'description'                => $this->description,
            'project_id'                 => $this->projectId,
            'approval_responsible_id'    => $this->approvalResponsibleId,
            'assignment_responsible_id'  => $this->assignmentResponsibleId,
            'duration_hours'             => $this->durationHours,
            'task_date'                  => $this->taskDate,
            'task_latitude'              => $this->taskLatitude,
            'task_longitude'             => $this->taskLongitude,
            'notes'                      => $this->notes,
            'status'                     => 'pending',
        ];
    }
}
