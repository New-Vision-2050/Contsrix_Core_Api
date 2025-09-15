<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoBusinessActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
