<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Illuminate\Http\UploadedFile;

final class RequestProjectNotificationFineDTO
{
    /**
     * @param array<int, array{name_ar: string, name_en?: string|null, quantity: int, unit_amount: float, total_amount: float, sort_order?: int|null}> $items
     * @param array<int, UploadedFile>|null $files
     */
    public function __construct(
        public readonly ?string $reason = null,
        public readonly array $items = [],
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
        public readonly ?float $currentLatitude = null,
        public readonly ?float $currentLongitude = null,
    ) {}

    public function totalAmount(): float
    {
        return array_reduce(
            $this->items,
            static fn (float $carry, array $item): float => $carry + (float) ($item['total_amount'] ?? 0),
            0.0,
        );
    }
}
