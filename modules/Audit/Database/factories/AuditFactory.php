<?php

declare(strict_types=1);

namespace Modules\Audit\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Audit\Models\Audit;
use Modules\User\Models\User;

/** @extends Factory<Audit> */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::first()->id,
            "user_type" => User::class,
            "event"=>"create",
            'auditable_id' => $this->faker->randomNumber(),
            'auditable_type' => $this->faker->word(),
            'url' => $this->faker->url(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'tags' => $this->faker->word(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
