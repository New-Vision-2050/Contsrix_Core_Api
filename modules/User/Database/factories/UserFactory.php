<?php

declare(strict_types=1);

namespace Modules\User\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\User\Models\User;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone_code' => $this->faker->countryCode(),
            'phone' => $this->faker->phoneNumber(),
            'global_company_user_id' => $this->faker->uuid(),
        ];
    }
}
