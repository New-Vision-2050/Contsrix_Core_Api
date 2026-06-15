<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Presenters;

use Modules\Shared\InternalProcessType\Models\InternalProcessType;
use Modules\Shared\InternalProcessType\Support\InternalProcessTypePayload;

final class InternalProcessTypePresenter
{
    public function __construct(private readonly InternalProcessType $type) {}

    public function toArray(): array
    {
        $payload = InternalProcessTypePayload::unpack($this->type->settings);

        return [
            'id'          => $this->type->id,
            'entity_type' => $this->type->entity_type,
            'name'        => $this->type->name,
            'is_active'   => $this->type->is_active,
            'sort_order'  => $this->type->sort_order,
            'form'        => $payload['form_detail'],
            'conditions'  => $payload['conditions'],
            'ordering'    => $payload['ordering'],
            'created_at'  => $this->type->created_at?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->type->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function single(InternalProcessType $type): array
    {
        return (new self($type))->toArray();
    }

    public static function collection(iterable $types): array
    {
        $result = [];
        foreach ($types as $type) {
            $result[] = (new self($type))->toArray();
        }

        return $result;
    }
}
