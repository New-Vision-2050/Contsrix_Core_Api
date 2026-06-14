<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Presenters;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Models\InternalProcessType;

final class InternalProcessTypePresenter
{
    public function __construct(private readonly InternalProcessType $type) {}

    public function toArray(): array
    {
        return [
            'id'          => $this->type->id,
            'entity_type' => $this->type->entity_type,
            'name'        => $this->type->name,
            'is_active'   => $this->type->is_active,
            'sort_order'  => $this->type->sort_order,
            'settings'    => $this->type->settings ?? InternalProcessCondition::defaultSettings(),
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
