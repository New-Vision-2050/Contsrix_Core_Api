<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\BankTypeAccount\Models\BankTypeAccount;

/** @extends Factory<BankTypeAccount> */
class BankTypeAccountFactory extends Factory
{
    protected $model = BankTypeAccount::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->code(),
        ];
    }
}
