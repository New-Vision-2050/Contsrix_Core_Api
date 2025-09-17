<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
