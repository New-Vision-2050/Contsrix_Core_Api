<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\FilterAttachmentRequestChartsDTO;

class FilterAttachmentRequestChartsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requestStatuses = 'pending|semi-approved|approved|declined';
        $itemStatuses = 'pending|approved|declined|update_requested';

        return [
            'project_id'                 => ['nullable', 'uuid', 'exists:projects,id'],
            'direction'                  => ['nullable', 'string', 'in:incoming,outgoing'],
            'type'                       => ['nullable', 'string', "regex:/^($requestStatuses)(,($requestStatuses))*$/"],
            'status'                     => ['nullable', 'string', "regex:/^($requestStatuses)(,($requestStatuses))*$/"],
            'procedure_setting_id'       => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'project_requirement_id'     => ['nullable', 'uuid', 'exists:project_requirements,id'],
            'attachment_type_id'         => ['nullable', 'uuid', 'exists:folders,id'],
            'item_status'                => ['nullable', 'string', "regex:/^($itemStatuses)(,($itemStatuses))*$/"],
            'file_type'                  => ['nullable', 'string', 'max:255'],
            'date_from'                  => ['nullable', 'date_format:Y-m-d'],
            'date_to'                    => ['nullable', 'date_format:Y-m-d'],
            'name'                       => ['nullable', 'string', 'max:255'],
            'contractual_engagement_key' => ['nullable', 'string', 'exists:contractual_engagements,code'],
        ];
    }

    public function toDTO(): FilterAttachmentRequestChartsDTO
    {
        return new FilterAttachmentRequestChartsDTO(
            projectId: $this->input('project_id'),
            direction: $this->input('direction'),
            status: $this->input('type') ?: $this->input('status'),
            procedureSettingId: $this->input('procedure_setting_id'),
            projectRequirementId: $this->input('project_requirement_id'),
            attachmentTypeId: $this->input('attachment_type_id'),
            itemStatus: $this->input('item_status'),
            fileType: $this->input('file_type'),
            dateFrom: $this->input('date_from'),
            dateTo: $this->input('date_to'),
            name: $this->input('name'),
            contractualEngagementKey: $this->input('contractual_engagement_key'),
        );
    }
}
