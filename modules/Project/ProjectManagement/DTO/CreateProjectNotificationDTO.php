<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class CreateProjectNotificationDTO
{
    public function __construct(
        public readonly string  $projectId,
        public readonly string  $createdByUserId,
        public readonly string  $assignedUserId,
        public readonly string  $taskDate,
        public readonly float   $durationHours,
        public readonly float   $taskLatitude,
        public readonly float   $taskLongitude,
        public readonly ?string $notificationType          = null,
        public readonly ?string $severity                  = 'منخفض',
        public readonly ?string $workType                  = null,
        public readonly ?string $feederNumber              = null,
        public readonly ?string $workDescription           = null,
        public readonly ?string $contractorId              = null,
        public readonly ?string $contractorName            = null,
        public readonly ?string $contractorNumber          = null,
        public readonly ?string $contractorTechnicalNumber = null,
        public readonly ?string $contractorTechnicalName   = null,
        public readonly ?string $contractorCategory        = null,
        public readonly ?string $contractorNotes           = null,
        public readonly ?string $contractorMobile          = null,
        public readonly ?int    $locationRadius            = null,
        public readonly ?string $locationLink              = null,
        public readonly ?string $repairPoint               = null,
        public readonly ?int    $selectedDistanceMeters    = null,
        public readonly ?string $notes                     = null,
        public readonly ?array  $files                     = null,
        public readonly ?string $approvalResponsibleId     = null,
        public readonly ?string $assignmentResponsibleId   = null,
    ) {}

    public function toArray(): array
    {
        return [
            'project_id'                  => $this->projectId,
            'created_by_user_id'          => $this->createdByUserId,
            'assigned_user_id'            => $this->assignedUserId,
            'task_date'                   => $this->taskDate,
            'duration_hours'              => $this->durationHours,
            'task_latitude'               => $this->taskLatitude,
            'task_longitude'              => $this->taskLongitude,
            'notification_type'           => $this->notificationType,
            'severity'                    => $this->severity,
            'work_type'                   => $this->workType,
            'feeder_number'               => $this->feederNumber,
            'work_description'            => $this->workDescription,
            'contractor_id'               => $this->contractorId,
            'contractor_name'             => $this->contractorName,
            'contractor_number'           => $this->contractorNumber,
            'contractor_technical_number' => $this->contractorTechnicalNumber,
            'contractor_technical_name'   => $this->contractorTechnicalName,
            'contractor_category'         => $this->contractorCategory,
            'contractor_notes'            => $this->contractorNotes,
            'contractor_mobile'           => $this->contractorMobile,
            'location_radius'             => $this->locationRadius,
            'location_link'               => $this->locationLink,
            'repair_point'                => $this->repairPoint,
            'selected_distance_meters'    => $this->selectedDistanceMeters,
            'notes'                       => $this->notes,
        ];
    }
}
