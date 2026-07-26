<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class FilterAttachmentRequestChartsDTO
{
    public function __construct(
        public readonly ?string $projectId = null,
        public readonly ?string $direction = null,
        public readonly ?string $status = null,
        public readonly ?string $procedureSettingId = null,
        public readonly ?string $projectRequirementId = null,
        public readonly ?string $attachmentTypeId = null,
        public readonly ?string $itemStatus = null,
        public readonly ?string $fileType = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $name = null,
        public readonly ?string $contractualEngagementKey = null,
    ) {}

    /**
     * @return list<string>
     */
    public function statusValues(): array
    {
        return $this->csvValues($this->status);
    }

    /**
     * @return list<string>
     */
    public function itemStatusValues(): array
    {
        return $this->csvValues($this->itemStatus);
    }

    /**
     * @return list<string>
     */
    private function csvValues(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $item): string => trim($item), explode(',', $value)),
            static fn (string $item): bool => $item !== '',
        ));
    }
}
