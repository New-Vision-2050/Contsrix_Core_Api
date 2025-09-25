<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;

/** @extends Factory<EcoAppSetting> */
class EcoAppSettingFactory extends Factory
{
    protected $model = EcoAppSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
