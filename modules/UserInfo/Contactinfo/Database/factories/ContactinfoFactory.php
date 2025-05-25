<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\Contactinfo\Models\ContactInfo;

/** @extends Factory<ContactInfo> */
class ContactinfoFactory extends Factory
{
    protected $model = ContactInfo::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
