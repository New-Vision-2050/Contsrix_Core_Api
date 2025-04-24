<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ArchiveLibrary\Folder\Models\Folder;

/** @extends Factory<Folder> */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
