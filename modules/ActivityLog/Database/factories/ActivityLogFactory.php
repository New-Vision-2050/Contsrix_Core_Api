<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ActivityLog\Models\ActivityLog;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
