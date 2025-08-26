<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;

/** @extends Factory<LeavePolicy> */
class LeavePolicyFactory extends Factory
{
    protected $model = LeavePolicy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
