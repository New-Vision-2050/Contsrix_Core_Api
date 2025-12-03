<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\Founder\Commands\UpdateFounderCommand;
use Modules\WebsiteCMS\Founder\Handlers\UpdateFounderHandler;

class UpdateFounderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'job_title_ar' => 'required|string|max:255',
            'job_title_en' => 'required|string|max:255',
            'personal_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

    public function createUpdateFounderCommand(): UpdateFounderCommand
    {
        return new UpdateFounderCommand(
            id: Uuid::fromString($this->route('id')),
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            job_title: [
                'ar' => $this->get('job_title_ar'),
                'en' => $this->get('job_title_en'),
            ],
            personal_photo: $this->file('personal_photo'),
        );
    }
}
