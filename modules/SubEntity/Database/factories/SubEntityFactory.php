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
        $superEntityType = $this->faker->randomElement(['User', 'Company', 'Department']);
        return [
            'id' => $this->faker->uuid(),
            'super_entity' => $superEntityType,
            'origin_super_entity' => $superEntityType,
            'name' => $this->faker->unique()->word(),
            'icon' => $this->faker->randomElement([
                'TwoPersonsIcon',
                'PersonLoudIcon',
                'PersonIcon',
                'PersonSettingsIcon',
                'PersonCircleIcon',
                'PersonSmileIcon',
                'TwoPersonsCircleIcon',
                'PersonLockIcon',
            ]),
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
