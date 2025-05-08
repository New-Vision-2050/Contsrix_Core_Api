<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\NatureWork\Models\NatureWork;

/** @extends Factory<NatureWork> */
class NatureWorkFactory extends Factory
{
    protected $model = NatureWork::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
