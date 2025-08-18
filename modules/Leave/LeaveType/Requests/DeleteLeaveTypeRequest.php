<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteLeaveTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
