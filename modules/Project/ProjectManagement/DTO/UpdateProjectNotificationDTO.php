<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class UpdateProjectNotificationDTO
{
    public function __construct(
        public readonly ?string $notificationType          = null,
        public readonly ?string $severity                  = null,
        public readonly ?string $workType                  = null,
        public readonly ?string $magdyNumber               = null,
        public readonly ?string $workDescription           = null,
        public readonly ?string $contractorName            = null,
        public readonly ?string $contractorNumber          = null,
        public readonly ?string $contractorTechnicalNumber = null,
        public readonly ?string $contractorCategory        = null,
        public readonly ?string $contractorNotes           = null,
        public readonly ?string $contractorMobile          = null,
        public readonly ?float  $taskLatitude              = null,
        public readonly ?float  $taskLongitude             = null,
        public readonly ?int    $locationRadius            = null,
        public readonly ?string $locationLink              = null,
        public readonly ?string $repairPoint               = null,
        public readonly ?string $assignedUserId            = null,
        public readonly ?int    $selectedDistanceMeters    = null,
        public readonly ?string $taskDate                  = null,
        public readonly ?float  $durationHours             = null,
        public readonly ?string $notes                     = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'notification_type'           => $this->notificationType,
            'severity'                    => $this->severity,
            'work_type'                   => $this->workType,
            'magdy_number'                => $this->magdyNumber,
            'work_description'            => $this->workDescription,
            'contractor_name'             => $this->contractorName,
            'contractor_number'           => $this->contractorNumber,
            'contractor_technical_number' => $this->contractorTechnicalNumber,
            'contractor_category'         => $this->contractorCategory,
            'contractor_notes'            => $this->contractorNotes,
            'contractor_mobile'           => $this->contractorMobile,
            'task_latitude'               => $this->taskLatitude,
            'task_longitude'              => $this->taskLongitude,
            'location_radius'             => $this->locationRadius,
            'location_link'               => $this->locationLink,
            'repair_point'                => $this->repairPoint,
            'assigned_user_id'            => $this->assignedUserId,
            'selected_distance_meters'    => $this->selectedDistanceMeters,
            'task_date'                   => $this->taskDate,
            'duration_hours'              => $this->durationHours,
            'notes'                       => $this->notes,
        ], fn ($value) => $value !== null);
    }
}
