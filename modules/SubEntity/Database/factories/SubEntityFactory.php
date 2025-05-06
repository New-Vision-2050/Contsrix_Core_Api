<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SubEntity\Models\SubEntity;

/** @extends Factory<SubEntity> */
class SubEntityFactory extends Factory
{
    protected $model = SubEntity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
