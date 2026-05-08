<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Commands;

/**
 * Partial updates: only keys present in {@see $attributes} are applied.
 *
 * @phpstan-type Payload array<string, mixed>
 */
class UpdateProcedureSettingStepCommand
{
    /**
     * @param Payload $attributes
     */
    public function __construct(
        private readonly int $id,
        private readonly array $attributes,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Payload
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
