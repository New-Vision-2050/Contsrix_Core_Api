<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ProcedureSetting\DTO\CreateProcedureSettingDTO;

class CreateProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'type'         => 'required|string|in:client_request,price_offer,contract',
            'execute_type' => 'required|string|in:parallel,sequence',
            'icon'         => 'nullable|string|max:255',
            'percentage'   => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function createCreateProcedureSettingDTO(): CreateProcedureSettingDTO
    {
        return new CreateProcedureSettingDTO(
            name:         $this->get('name'),
            type:         $this->get('type'),
            execute_type: $this->get('execute_type'),
            icon:         $this->get('icon'),
            percentage:   (float) $this->get('percentage', 0),
        );
    }
}
