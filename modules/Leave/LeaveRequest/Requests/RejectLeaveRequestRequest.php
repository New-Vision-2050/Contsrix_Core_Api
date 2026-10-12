<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
        ];
    }

    public function reason(): string
    {
        return (string) $this->get('reason');
    }
}
