<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeletePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
