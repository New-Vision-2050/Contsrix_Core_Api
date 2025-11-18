<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteService\Commands\UpdateWebsiteServiceCommand;

class UpdateWebsiteServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
            'category_website_cms_id' => 'required|uuid|exists:category_website_cms,id',
            'reference_number' => 'nullable|string|unique:website_services,reference_number,' . $id,
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'previous_work' => 'nullable|array',
            'previous_work.*.description' => 'required|string',
            'previous_work.*.image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

    private function preparePreviousWork(): ?array
    {
        if (!$this->has('previous_work')) {
            return null;
        }

        $previousWork = [];
        $descriptions = $this->input('previous_work.*.description', []);
        $images = $this->file('previous_work.*.image', []);

        foreach ($descriptions as $index => $description) {
            $previousWork[] = [
                'description' => $description,
                'image' => $images[$index] ?? null,
            ];
        }

        return $previousWork;
    }

    public function toCommand(): UpdateWebsiteServiceCommand
    {
        return new UpdateWebsiteServiceCommand(
            id: $this->route('id'),
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            main_image: $this->file('main_image'),
            icon: $this->file('icon'),
            category_website_cms_id: $this->get('category_website_cms_id'),
            reference_number: $this->get('reference_number'),
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            previous_work: $this->preparePreviousWork()
        );
    }
}
