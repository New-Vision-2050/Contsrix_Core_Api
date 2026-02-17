<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\ProjectType\Models\ProjectType;

/** @extends Factory<ProjectType> */
class ProjectTypeFactory extends Factory
{
    protected $model = ProjectType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
