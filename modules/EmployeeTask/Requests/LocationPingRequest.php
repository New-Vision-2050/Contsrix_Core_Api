<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationPingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timestamp' => ['required', 'string'],
        ];
    }
}
