<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Illuminate\Http\UploadedFile;

final class RequestProjectNotificationUpdateDTO
{
    /**
     * @param array<int, UploadedFile>|null $files
     */
    public function __construct(
        public readonly ?string $notificationType = null,
        public readonly ?string $feederNumber = null,
        public readonly ?string $workDescription = null,
        public readonly ?string $contractorName = null,
        public readonly ?string $contractorTechnicalName = null,
        public readonly ?string $contractorMobile = null,
        public readonly ?float $taskLatitude = null,
        public readonly ?float $taskLongitude = null,
        public readonly ?string $notes = null,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'notification_type'           => $this->notificationType,
            'feeder_number'               => $this->feederNumber,
            'work_description'            => $this->workDescription,
            'contractor_name'             => $this->contractorName,
            'contractor_technical_name'   => $this->contractorTechnicalName,
            'contractor_mobile'           => $this->contractorMobile,
            'task_latitude'               => $this->taskLatitude,
            'task_longitude'              => $this->taskLongitude,
            'notes'                       => $this->notes,
        ], static fn ($value) => $value !== null);
    }
}
