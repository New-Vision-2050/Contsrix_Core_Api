<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class FilterProjectNotificationDTO
{
    public function __construct(
        public readonly ?string $projectId       = null,
        public readonly ?string $status          = null,
        public readonly ?string $notificationType = null,
        public readonly ?string $workType        = null,
        public readonly ?string $contractorName  = null,
        public readonly ?string $assignedUserId  = null,
        public readonly ?string $dateFrom        = null,
        public readonly ?string $dateTo          = null,
        public readonly ?string $search          = null,
        public readonly ?int    $perPage         = 15,
        public readonly ?string $sort            = null,
    ) {}

    public function toFilters(): array
    {
        return array_filter([
            'project_id'        => $this->projectId,
            'status'            => $this->status,
            'notification_type' => $this->notificationType,
            'work_type'         => $this->workType,
            'contractor_name'   => $this->contractorName,
            'assigned_user_id'  => $this->assignedUserId,
            'date_from'         => $this->dateFrom,
            'date_to'           => $this->dateTo,
            'search'            => $this->search,
        ], fn ($value) => $value !== null);
    }
}
