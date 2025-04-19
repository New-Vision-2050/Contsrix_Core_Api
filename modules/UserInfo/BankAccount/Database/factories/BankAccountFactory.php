<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\BankAccount\Models\BankAccount;

/** @extends Factory<BankAccount> */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
