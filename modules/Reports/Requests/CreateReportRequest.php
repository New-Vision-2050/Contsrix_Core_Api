<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reports\DTO\CreateReportDTO;
use Modules\Reports\Requests\Traits\ValidatesWizardConfig;

class CreateReportRequest extends FormRequest
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
                // Optional translatable display name override (when omitted the
                // service auto-generates a name from report types + period).
                'name'    => 'nullable|array',
                'name.ar' => 'nullable|string|max:255',
                'name.en' => 'nullable|string|max:255',

                // Optional lineage back to a saved template.
                'template_id' => 'nullable|uuid|exists:report_templates,id',
            ],
            $this->wizardConfigRules('config.')
        );
    }

    public function toDTO(): CreateReportDTO
    {
        $name = $this->input('name');

        return new CreateReportDTO(
            config:     $this->buildWizardConfig('config'),
            name:       is_array($name) ? $name : null,
            templateId: $this->input('template_id'),
        );
    }
}
