<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;

/** @extends Factory<WebsiteNews> */
class WebsiteNewsFactory extends Factory
{
    protected $model = WebsiteNews::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
