<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\RightTerminate\Models\RightTerminate;

/** @extends Factory<RightTerminate> */
class RightTerminateFactory extends Factory
{
    protected $model = RightTerminate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
