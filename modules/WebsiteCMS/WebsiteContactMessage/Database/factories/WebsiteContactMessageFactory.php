<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;

/** @extends Factory<WebsiteContactMessage> */
class WebsiteContactMessageFactory extends Factory
{
    protected $model = WebsiteContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
