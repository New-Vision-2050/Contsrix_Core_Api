<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\TermSetting\Models\TermSetting;

/** @extends Factory<TermSetting> */
class TermSettingFactory extends Factory
{
    protected $model = TermSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
