<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\RoleAndPermission\Models\RoleAndPermission;

/** @extends Factory<RoleAndPermission> */
class RoleAndPermissionFactory extends Factory
{
    protected $model = RoleAndPermission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
