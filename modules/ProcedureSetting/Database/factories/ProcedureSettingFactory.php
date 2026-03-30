<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ProcedureSetting\Models\ProcedureSetting;

/** @extends Factory<ProcedureSetting> */
class ProcedureSettingFactory extends Factory
{
    protected $model = ProcedureSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
