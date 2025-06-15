<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Privilege\Models\Privilege;

/** @extends Factory<Privilege> */
class PrivilegeFactory extends Factory
{
    protected $model = Privilege::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
