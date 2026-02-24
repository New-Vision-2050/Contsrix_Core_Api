<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteTermServicesRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
