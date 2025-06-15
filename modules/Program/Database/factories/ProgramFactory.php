<?php

declare(strict_types=1);

namespace Modules\Program\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Program\Models\Program;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
