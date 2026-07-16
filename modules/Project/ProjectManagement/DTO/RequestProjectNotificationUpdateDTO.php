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
        public readonly ?string $machineNumber = null,
        public readonly ?string $workDescription = null,
        public readonly ?string $contractorName = null,
        public readonly ?string $contractorTechnicalName = null,
        public readonly ?string $contractorMobile = null,
        public readonly ?float $taskLatitude = null,
        public readonly ?float $taskLongitude = null,
        public readonly ?string $permitSource = null,
        public readonly ?string $permitRecipient = null,
        public readonly ?string $notes = null,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
        public readonly ?string $siteStatusTypeId = null,
        public readonly ?array $siteStatusTypeValues = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'notification_type'           => $this->notificationType,
            'feeder_number'               => $this->feederNumber,
            'machine_number'              => $this->machineNumber,
            'work_description'            => $this->workDescription,
            'contractor_name'             => $this->contractorName,
            'contractor_technical_name'   => $this->contractorTechnicalName,
            'contractor_mobile'           => $this->contractorMobile,
            'task_latitude'               => $this->taskLatitude,
            'task_longitude'              => $this->taskLongitude,
            'permit_source'               => $this->permitSource,
            'permit_recipient'            => $this->permitRecipient,
            'notes'                       => $this->notes,
            'site_status_type_id'         => $this->siteStatusTypeId,
            'site_status_type_values'     => $this->siteStatusTypeValues,
        ], static fn ($value) => $value !== null);
    }
}
