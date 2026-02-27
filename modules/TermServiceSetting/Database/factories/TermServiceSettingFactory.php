<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\TermServiceSetting\Models\TermServiceSetting;

/** @extends Factory<TermServiceSetting> */
class TermServiceSettingFactory extends Factory
{
    protected $model = TermServiceSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
