<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class FilterProjectNotificationChartsDTO
{
    public function __construct(
        public readonly ?string $projectId                = null,
        public readonly ?string $status                  = null,
        public readonly ?string $notificationType         = null,
        public readonly ?string $workType                 = null,
        public readonly ?string $contractorName           = null,
        public readonly ?string $contractorId             = null,
        public readonly ?string $contractorCategory       = null,
        public readonly ?string $severity                 = null,
        public readonly ?string $assignedUserId           = null,
        public readonly ?string $taskDate                 = null,
        public readonly ?string $dateFrom                 = null,
        public readonly ?string $dateTo                   = null,
        public readonly ?string $search                   = null,
        public readonly ?string $contractualEngagementKey = null,
    ) {}

    /**
     * Convert to filter array, optionally excluding a dimension for cross-filtering.
     *
     * @param  string|null  $excludeDimension  The dimension to exclude (e.g. 'status', 'notification_type')
     * @return array
     */
    public function toFilters(?string $excludeDimension = null): array
    {
        $filters = [
            'project_id'        => $this->projectId,
            'status'            => $this->status,
            'notification_type' => $this->notificationType,
            'work_type'         => $this->workType,
            'contractor_name'   => $this->contractorName,
            'contractor_id'     => $this->contractorId,
            'contractor_category' => $this->contractorCategory,
            'severity'          => $this->severity,
            'assigned_user_id'  => $this->assignedUserId,
            'task_date'         => $this->taskDate,
            'date_from'         => $this->dateFrom,
            'date_to'           => $this->dateTo,
            'search'            => $this->search,
            'contractual_engagement_key' => $this->contractualEngagementKey,
        ];

        if ($excludeDimension !== null && array_key_exists($excludeDimension, $filters)) {
            $filters[$excludeDimension] = null;
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }

    /**
     * Convert to filter array without any exclusions.
     */
    public function toAllFilters(): array
    {
        return $this->toFilters(null);
    }
}
