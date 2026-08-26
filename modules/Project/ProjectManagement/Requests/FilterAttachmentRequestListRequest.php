<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;

class FilterAttachmentRequestListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'                 => ['nullable', 'uuid', 'exists:projects,id'],
            'contractual_engagement_key' => ['nullable', 'string', 'max:255'],
            'procedure_setting_id'       => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'receiver_company_ids'       => ['nullable', 'array', 'min:1'],
            'receiver_company_ids.*'     => ['required', 'uuid', 'distinct', 'exists:companies,id'],
            'type'                       => [
                'nullable',
                'string',
                Rule::in([
                    AttachmentRequest::STATUS_PENDING,
                    AttachmentRequest::STATUS_SEMI_APPROVED,
                    AttachmentRequest::STATUS_APPROVED,
                    AttachmentRequest::STATUS_DECLINED,
                ]),
            ],
            'direction' => ['nullable', 'string', Rule::in(['incoming', 'outgoing'])],
            'name'      => ['nullable', 'string', 'max:255'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->only([
                'project_id',
                'contractual_engagement_key',
                'procedure_setting_id',
                'receiver_company_ids',
                'type',
                'direction',
                'name',
                'per_page',
            ]),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }
}
