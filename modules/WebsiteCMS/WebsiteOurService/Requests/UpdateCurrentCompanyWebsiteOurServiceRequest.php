<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\WebsiteCMS\WebsiteOurService\DTO\CreateWebsiteOurServiceDTO;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;

class UpdateCurrentCompanyWebsiteOurServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',

            // Departments array validation
            'departments' => 'nullable|array',
            'departments.*.title_ar' => 'required|string|max:255',
            'departments.*.title_en' => 'required|string|max:255',
            'departments.*.description_ar' => 'nullable|string',
            'departments.*.description_en' => 'nullable|string',
            'departments.*.type' => ['required', 'string', Rule::in(ServiceTypeEnum::values())],
            'departments.*.website_service_ids' => 'required|array|min:1',
            'departments.*.website_service_ids.*' => 'required|uuid|exists:website_services,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $departments = $this->input('departments', []);

            foreach ($departments as $index => $department) {
                if (isset($department['type']) && $department['type'] === ServiceTypeEnum::HEXA->value) {
                    $serviceIds = $department['website_service_ids'] ?? [];
                    if (count($serviceIds) !== 6) {
                        $validator->errors()->add(
                            "departments.{$index}.website_service_ids",
                            "When type is 'hexa', exactly 6 website service IDs are required. You provided " . count($serviceIds) . "."
                        );
                    }
                }
            }
        });
    }

    public function toDTO(): CreateWebsiteOurServiceDTO
    {
        return new CreateWebsiteOurServiceDTO(
            title: $this->get("title"),
            description:$this->get("description"),
            departments: $this->get('departments', []),
            status: $this->get('status', 1),
        );
    }
}
