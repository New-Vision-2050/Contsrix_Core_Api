<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

/** @extends Factory<ProjectManagement> */
class ProjectManagementFactory extends Factory
{
    protected $model = ProjectManagement::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
