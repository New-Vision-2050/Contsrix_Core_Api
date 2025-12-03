<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;

/** @extends Factory<WebsiteProject> */
class WebsiteProjectFactory extends Factory
{
    protected $model = WebsiteProject::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
