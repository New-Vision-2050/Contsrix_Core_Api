<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;

/** @extends Factory<WebsiteHomePageSetting> */
class WebsiteHomePageSettingFactory extends Factory
{
    protected $model = WebsiteHomePageSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
