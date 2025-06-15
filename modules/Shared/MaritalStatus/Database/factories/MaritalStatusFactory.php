<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\MaritalStatus\Models\MaritalStatus;

/** @extends Factory<MaritalStatus> */
class MaritalStatusFactory extends Factory
{
    protected $model = MaritalStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
