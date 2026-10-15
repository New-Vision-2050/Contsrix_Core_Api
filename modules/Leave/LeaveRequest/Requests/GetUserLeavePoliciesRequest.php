<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserLeavePoliciesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer|min:2000|max:2100',
        ];
    }

    public function year(): ?int
    {
        $year = $this->get('year');

        return $year !== null ? (int) $year : null;
    }
}
