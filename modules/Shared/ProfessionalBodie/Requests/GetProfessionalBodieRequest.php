<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProfessionalBodieRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
