<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

final class CreateEmployeeTaskTypeDTO
{
    public function __construct(public readonly string $key, public readonly string $name) {}
    public function toArray(): array { return ['key' => $this->key, 'name' => $this->name]; }
}
