<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteCMS\WebsiteAddress\Models\WebsiteAddress;

/** @extends Factory<WebsiteAddress> */
class WebsiteAddressFactory extends Factory
{
    protected $model = WebsiteAddress::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
