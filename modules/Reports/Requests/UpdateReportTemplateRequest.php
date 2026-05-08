<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reports\DTO\UpdateReportTemplateDTO;
use Modules\Reports\Requests\Traits\ValidatesWizardConfig;
use Ramsey\Uuid\Uuid;

class UpdateReportTemplateRequest extends FormRequest
{
    use ValidatesWizardConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            [
                'name'           => 'required|array',
                'name.ar'        => 'required|string|max:255',
                'name.en'        => 'required|string|max:255',
                'description'    => 'nullable|array',
                'description.ar' => 'nullable|string|max:2000',
                'description.en' => 'nullable|string|max:2000',
                'is_active'      => 'nullable|boolean',
            ],
            $this->wizardConfigRules('config.')
        );
    }

    public function toDTO(): UpdateReportTemplateDTO
    {
        $description = $this->input('description');

        return new UpdateReportTemplateDTO(
            id:          Uuid::fromString((string) $this->route('id')),
            name:        (array) $this->input('name'),
            description: is_array($description) ? $description : null,
            config:      $this->buildWizardConfig('config'),
            isActive:    $this->has('is_active') ? (bool) $this->input('is_active') : null,
        );
    }
}
