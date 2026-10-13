<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin inbox reject body — mirrors admin/employee-tasks reject
 * (field name `rejection_reason`, same criteria).
 */
class AdminRejectAdvanceRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|max:1000',
        ];
    }

    public function rejectionReason(): string
    {
        return (string) $this->get('rejection_reason');
    }
}
