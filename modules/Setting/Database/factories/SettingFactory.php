<?php

declare(strict_types=1);

namespace Modules\Setting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setting\Models\Setting;

/** @extends Factory<Setting> */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->name(),
            'value' => $this->faker->name(),
        ];
    }
}
