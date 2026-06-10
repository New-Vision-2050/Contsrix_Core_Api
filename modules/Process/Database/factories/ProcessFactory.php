<?php

declare(strict_types=1);

namespace Modules\Process\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Process\Models\Process;

/** @extends Factory<Process> */
class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
