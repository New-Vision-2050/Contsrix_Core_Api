<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;

/** @extends Factory<WebsiteSetting> */
class WebsiteSettingFactory extends Factory
{
    protected $model = WebsiteSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
