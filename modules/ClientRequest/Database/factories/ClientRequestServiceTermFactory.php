<?php

namespace Modules\ClientRequest\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ClientRequest\Models\ClientRequestServiceTerm;

class ClientRequestServiceTermFactory extends Factory
{
    protected $model = ClientRequestServiceTerm::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'client_request_id' => $this->faker->uuid(),
            'client_request_service_id' => 1,
            'term_ids' => [1, 2, 3],
            'company_id' => $this->faker->uuid(),
        ];
    }
}
