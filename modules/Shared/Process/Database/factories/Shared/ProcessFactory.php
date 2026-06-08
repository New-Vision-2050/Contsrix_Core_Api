<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared/Process\Models\Shared/Process;

/** @extends Factory<Shared/Process> */
class Shared/ProcessFactory extends Factory
{
    protected $model = Shared/Process::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
