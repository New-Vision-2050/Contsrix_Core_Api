<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ClientRequest\Models\ClientRequest;

/** @extends Factory<ClientRequest> */
class ClientRequestFactory extends Factory
{
    protected $model = ClientRequest::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
