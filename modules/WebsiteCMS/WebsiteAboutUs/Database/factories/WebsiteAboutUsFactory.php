<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;

/** @extends Factory<WebsiteAboutUs> */
class WebsiteAboutUsFactory extends Factory
{
    protected $model = WebsiteAboutUs::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
