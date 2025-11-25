<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteOurService\Commands\UpdateWebsiteOurServiceCommand;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;

class UpdateWebsiteOurServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',

            // Departments array validation
            'departments' => 'required|array|min:1',
            'departments.*.title_ar' => 'nullable|string|max:255',
            'departments.*.title_en' => 'nullable|string|max:255',
            'departments.*.description_ar' => 'nullable|string',
            'departments.*.description_en' => 'nullable|string',
            'departments.*.type' => ['nullable', 'string', Rule::in(ServiceTypeEnum::values())],
            'departments.*.website_service_ids' => 'nullable|array|min:1',
            'departments.*.website_service_ids.*' => 'nullable|uuid|exists:website_services,id',
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

    public function createUpdateWebsiteOurServiceCommand(): UpdateWebsiteOurServiceCommand
    {
        return new UpdateWebsiteOurServiceCommand(
            id: Uuid::fromString($this->route('id')),
            title: $this->get("title"),
            description: $this->get("description"),
            departments: $this->get('departments', []),
            status: $this->get('status', 1),
        );
    }
}
