<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;

/** @extends Factory<ProgramSystem> */
class ProgramSystemFactory extends Factory
{
    protected $model = ProgramSystem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
