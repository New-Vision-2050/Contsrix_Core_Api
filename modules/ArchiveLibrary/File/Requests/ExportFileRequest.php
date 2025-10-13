<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'format' => 'sometimes|string|in:xlsx,csv',
            'ids' => 'sometimes|array',
            'ids.*' => 'sometimes|string|uuid',
        ];
    }

    /**
     * Get filters from request for export
     */
    public function getFilters(): array
    {
        $filters = [];

        // Add ID filtering
        if ($this->has('ids')) {
            $filters['ids'] = $this->get('ids');
        }

        // Add any other filters if needed
        if ($this->has('search')) {
            $filters['search'] = $this->get('search');
        }

        if ($this->has('status')) {
            $filters['status'] = $this->get('status');
        }

        if ($this->has('folder_id')) {
            $filters['folder_id'] = $this->get('folder_id');
        }

        return $filters;
    }
}
