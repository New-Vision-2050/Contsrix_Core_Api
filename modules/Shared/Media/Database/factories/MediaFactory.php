<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Media\Models\Media;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
