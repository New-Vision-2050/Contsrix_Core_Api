<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\LeaveType\Models\LeaveType;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
