<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reports\DTO\CreateReportTemplateDTO;
use Modules\Reports\Requests\Traits\ValidatesWizardConfig;

class CreateReportTemplateRequest extends FormRequest
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
            ],
            $this->wizardConfigRules('config.')
        );
    }

    public function toDTO(): CreateReportTemplateDTO
    {
        $description = $this->input('description');

        return new CreateReportTemplateDTO(
            name:        (array) $this->input('name'),
            description: is_array($description) ? $description : null,
            config:      $this->buildWizardConfig('config'),
        );
    }
}
