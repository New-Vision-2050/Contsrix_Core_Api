<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\EcoBankAccount\Models\EcoBankAccount;

/** @extends Factory<EcoBankAccount> */
class EcoBankAccountFactory extends Factory
{
    protected $model = EcoBankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
