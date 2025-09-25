<?php

declare(strict_types=1);

namespace Modules\DocumentType\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DocumentType\Models\DocumentType;

/** @extends Factory<DocumentType> */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
