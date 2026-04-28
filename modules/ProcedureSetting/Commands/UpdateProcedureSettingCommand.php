<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Commands;

use Illuminate\Support\Arr;
use Ramsey\Uuid\UuidInterface;

/**
 * Carries only validated keys from the request so partial PATCH-style bodies do not
 * wipe columns that were omitted from the payload.
 *
 * @phpstan-type Payload array<string, mixed>
 */
class UpdateProcedureSettingCommand
{
    /**
     * @param Payload $attributes
     */
    public function __construct(
        private readonly UuidInterface $id,
        private readonly array $attributes,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return Payload
     */
    public function toArray(): array
    {
        $keys = [
            'name',
            'type',
            'execute_type',
            'icon',
            'percentage',
            'deadline_days',
            'deadline_hours',
            'escalation_user_id',
            'work_flow_id',
        ];

        $data = Arr::only($this->attributes, $keys);

        if (array_key_exists('percentage', $data) && $data['percentage'] !== null) {
            $data['percentage'] = (float) $data['percentage'];
        }

        foreach (['deadline_days', 'deadline_hours'] as $intKey) {
            if (! array_key_exists($intKey, $data)) {
                continue;
            }
            if ($data[$intKey] === null) {
                continue;
            }
            $data[$intKey] = (int) $data[$intKey];
        }

        if (array_key_exists('escalation_user_id', $data) && $data['escalation_user_id'] !== null) {
            $data['escalation_user_id'] = (string) $data['escalation_user_id'];
        }

        if (array_key_exists('work_flow_id', $data) && $data['work_flow_id'] !== null) {
            $data['work_flow_id'] = (string) $data['work_flow_id'];
        }

        return $data;
    }
}
