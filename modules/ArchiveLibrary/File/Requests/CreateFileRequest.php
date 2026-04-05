<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\File\DTO\CreateFileDTO;

class CreateFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'reference_number' => [
                'required',
                Rule::unique('files', 'reference_number')->where(function ($query) {
                    return $query->where('company_id', tenant('id'));
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_ids' => 'required_if:access_type,private|array',
            'user_ids.*' => 'sometimes|exists:users,id',
            "access_type"=>"required|in:private,public",
            "file"=>"required|mimes:pdf,jpeg,jpg,png,doc,docx",
            "parent_id"=>"nullable|exists:folders,id",
            "project_id"=>"nullable|uuid|exists:projects,id",
            "status"=>"sometimes|integer|in:0,1"
        ];
    }

    public function createCreateFileDTO(): CreateFileDTO
    {
        return new CreateFileDTO(
            name: $this->get('name'),
            referenceNumber: $this->get('reference_number'),
            startDate: $this->get('start_date'),
            endDate: $this->get('end_date'),
            userIds: $this->get('user_ids', []),
            file: $this->file('file'),
            accessType: $this->get('access_type'),
            folderId: $this->get('parent_id'),
            projectId: $this->get('project_id'),
            status: (int) $this->get('status', 1)
        );
    }
}
