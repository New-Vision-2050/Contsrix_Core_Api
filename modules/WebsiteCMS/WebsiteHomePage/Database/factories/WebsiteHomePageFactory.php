<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteHomePage\Models\WebsiteHomePage;

/** @extends Factory<WebsiteHomePage> */
class WebsiteHomePageFactory extends Factory
{
    protected $model = WebsiteHomePage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
