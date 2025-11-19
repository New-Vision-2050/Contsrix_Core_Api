<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteProjectSetting\Models\WebsiteProjectSetting;

/** @extends Factory<WebsiteProjectSetting> */
class WebsiteProjectSettingFactory extends Factory
{
    protected $model = WebsiteProjectSetting::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
