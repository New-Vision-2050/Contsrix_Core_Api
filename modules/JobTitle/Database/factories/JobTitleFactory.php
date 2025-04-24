<?php

declare(strict_types=1);

namespace Modules\JobTitle\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\JobTitle\Models\JobTitle;

/** @extends Factory<JobTitle> */
class JobTitleFactory extends Factory
{
    protected $model = JobTitle::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
