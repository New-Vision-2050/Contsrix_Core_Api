<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

/** @extends Factory<ManagementHierarchy> */
class ManagementHierarchyFactory extends Factory
{
    protected $model = ManagementHierarchy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
