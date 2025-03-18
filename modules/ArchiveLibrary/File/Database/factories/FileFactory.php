<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ArchiveLibrary\File\Models\File;

/** @extends Factory<File> */
class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
