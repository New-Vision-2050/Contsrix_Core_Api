<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:0,1',
        ];
    }

    public function getStatus(): int
    {
        return (int) $this->get('status');
    }
}
