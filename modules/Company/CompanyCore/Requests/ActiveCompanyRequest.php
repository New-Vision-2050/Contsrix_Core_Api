<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\ActivateCompanyCommand;

class ActiveCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "is_active" => "required",
            "date_activate" => "nullable|required_if:is_active,1|date|after_or_equal:today"
        ];
    }

    public function createActiveCompanyCommand(): ActivateCompanyCommand
    {
        return new ActivateCompanyCommand(
            id: Uuid::fromString($this->route('id')),
            is_active: (int) $this->get('is_active'),  // Cast to int
            date_activate: $this->get('date_activate'),
        );
    }
}
