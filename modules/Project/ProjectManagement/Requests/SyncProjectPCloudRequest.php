<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncProjectPCloudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $routeProjectId = $this->route('project');

        if (! $this->filled('project_id') && is_string($routeProjectId)) {
            $this->merge(['project_id' => $routeProjectId]);
        }
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid'],
        ];
    }

    public function projectId(): string
    {
        return (string) $this->validated('project_id');
    }
}
