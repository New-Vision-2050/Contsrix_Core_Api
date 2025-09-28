<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\NotificationSettings\Models\NotificationSettings;

/** @extends Factory<NotificationSettings> */
class NotificationSettingsFactory extends Factory
{
    protected $model = NotificationSettings::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
