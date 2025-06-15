<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;

/** @extends Factory<ProfessionalCertificate> */
class ProfessionalCertificateFactory extends Factory
{
    protected $model = ProfessionalCertificate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
