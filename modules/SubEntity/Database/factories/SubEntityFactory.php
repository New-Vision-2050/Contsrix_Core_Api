<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\SubEntity;

/** @extends Factory<SubEntity> */
class SubEntityFactory extends Factory
{
    protected $model = SubEntity::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'super_entity' => $this->faker->randomElement(['User', 'Company', 'Department']),
            'name' => $this->faker->unique()->word(),
            'icon' => $this->faker->numberBetween(0, 255),
            'main_program_id' => Program::first(), // TODO
            'is_active' => $this->faker->boolean(70), // 80% chance of being active
            'is_registrable' => $this->faker->boolean(75), // 30% chance of being registrable
            'default_attributes' => json_encode([
                'name',
                'email',
                'phone',
            ]),
            'optional_attributes' => $this->faker->optional()->passthrough(json_encode([
                "company_id",
                "is_owner",
                "management_hierarchy_id"
            ])),
        ];
    }
}
