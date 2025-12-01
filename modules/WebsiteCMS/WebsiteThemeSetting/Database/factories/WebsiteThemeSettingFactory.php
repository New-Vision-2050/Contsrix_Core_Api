<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;

/** @extends Factory<WebsiteThemeSetting> */
class WebsiteThemeSettingFactory extends Factory
{
    protected $model = WebsiteThemeSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
