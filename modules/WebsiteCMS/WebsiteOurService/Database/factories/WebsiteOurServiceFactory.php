<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;

/** @extends Factory<WebsiteOurService> */
class WebsiteOurServiceFactory extends Factory
{
    protected $model = WebsiteOurService::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
