<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteLeavePolicyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
