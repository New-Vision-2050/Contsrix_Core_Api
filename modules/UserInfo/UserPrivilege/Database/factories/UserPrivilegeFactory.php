<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;

/** @extends Factory<UserPrivilege> */
class UserPrivilegeFactory extends Factory
{
    protected $model = UserPrivilege::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
