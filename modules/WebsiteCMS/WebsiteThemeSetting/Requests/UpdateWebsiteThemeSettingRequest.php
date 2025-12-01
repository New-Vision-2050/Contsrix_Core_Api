<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteThemeSetting\DTO\UpdateWebsiteThemeSettingDTO;

class UpdateWebsiteThemeSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'about_ar' => 'nullable|string',
            'about_en' => 'nullable|string',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_default' => 'nullable|boolean',
            'departments' => 'nullable|array',
            'departments.*.name_ar' => 'required|string|max:255',
            'departments.*.name_en' => 'required|string|max:255',
        ];
    }

    public function toDTO(): UpdateWebsiteThemeSettingDTO
    {
        $title = null;
        $description = null;
        $about = null;

        if ($this->has('title_ar') || $this->has('title_en')) {
            $title = [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ];
        }

        if ($this->has('description_ar') || $this->has('description_en')) {
            $description = [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ];
        }

        if ($this->has('about_ar') || $this->has('about_en')) {
            $about = [
                'ar' => $this->get('about_ar'),
                'en' => $this->get('about_en'),
            ];
        }

        return new UpdateWebsiteThemeSettingDTO(
            title: $title,
            description: $description,
            about: $about,
            departments: $this->get('departments'),
            main_image: $this->file('main_image'),
            is_default: $this->has('is_default') ? $this->boolean('is_default') : null,
        );
    }
}
