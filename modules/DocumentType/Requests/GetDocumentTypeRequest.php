<?php

declare(strict_types=1);

namespace Modules\DocumentType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
