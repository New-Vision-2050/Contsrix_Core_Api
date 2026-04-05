<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\File\Commands\UpdateFileCommand;
use Modules\ArchiveLibrary\File\Handlers\UpdateFileHandler;

class UpdateFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'reference_number' => [
                'required',
                Rule::unique('files', 'reference_number')
                    ->ignore($this->route('id'))
                    ->where(function ($query) {
                        return $query->where('company_id', tenant('id'));
                    })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            "access_type" => "required|in:private,public",
            'user_ids' => 'required_if:access_type,private|array',
            'user_ids.*' => 'sometimes|exists:users,id',
            "file" => "nullable",
            "parent_id" => "nullable|exists:folders,id",
            "project_id"=>"nullable|uuid|exists:projects,id",
            "status" => "sometimes|integer|in:0,1"
        ];
    }

    public function createUpdateFileCommand(): UpdateFileCommand
    {
        return new UpdateFileCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            referenceNumber: $this->get('reference_number'),
            accessType: $this->get('access_type'),
            startDate: $this->get('start_date'),
            endDate: $this->get('end_date'),
            userIds: $this->get('user_ids', []),
            file: $this->hasFile('file') ? $this->file("file") : null,
            folderId: $this->get('folder_id'),
            projectId: $this->get('project_id'),
            status: $this->has('status') ? (int) $this->get('status') : null
        );
    }
}
