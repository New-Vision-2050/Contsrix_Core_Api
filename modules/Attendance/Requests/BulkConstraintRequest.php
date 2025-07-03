<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DTO\BulkConstraintIdsDTO;

class BulkConstraintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'constraint_ids' => 'required|array',
            'constraint_ids.*' => 'required|uuid'
        ];
    }

    /**
     * Create a DTO from the validated request data.
     */
    public function createBulkConstraintIdsDTO(): BulkConstraintIdsDTO
    {
        $validated = $this->validated();
        
        return new BulkConstraintIdsDTO(
            constraintIds: $validated['constraint_ids']
        );
    }
}
