<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\JobType\Models\JobType;

/** @extends Factory<JobType> */
class JobTypeFactory extends Factory
{
    protected $model = JobType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
