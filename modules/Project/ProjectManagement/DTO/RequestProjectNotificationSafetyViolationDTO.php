<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class RequestProjectNotificationSafetyViolationDTO
{
    /**
     * @param array<int, array{violation_id: string, weight?: mixed, status: string, action?: string|null, images?: array<int, \Illuminate\Http\UploadedFile>|null}> $violations
     */
    public function __construct(
        public readonly array $violations,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?float $currentLatitude = null,
        public readonly ?float $currentLongitude = null,
    ) {}

    /**
     * Serialise the violations for process metadata (strips UploadedFile objects).
     *
     * @return array<int, array{violation_id: string, weight?: mixed, status: string, action?: string|null}>
     */
    public function violationsForMetadata(): array
    {
        return array_map(static function (array $v): array {
            return array_filter([
                'violation_id' => $v['violation_id'],
                'weight'       => $v['weight'] ?? null,
                'status'       => $v['status'],
                'action'       => $v['action'] ?? null,
            ], static fn ($value) => $value !== null);
        }, $this->violations);
    }
}
