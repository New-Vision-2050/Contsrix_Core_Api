<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\LeavePolicyCategory\Models\LeavePolicyCategory;

/** @extends Factory<LeavePolicyCategory> */
class LeavePolicyCategoryFactory extends Factory
{
    protected $model = LeavePolicyCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
