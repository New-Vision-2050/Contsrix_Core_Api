<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;

/** @extends Factory<TypePrivilege> */
class TypePrivilegeFactory extends Factory
{
    protected $model = TypePrivilege::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
