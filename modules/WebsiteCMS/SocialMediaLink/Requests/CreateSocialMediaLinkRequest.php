<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\WebsiteCMS\SocialMediaLink\DTO\CreateSocialMediaLinkDTO;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;

class CreateSocialMediaLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(SocialMediaType::values())],
            'link' => 'required|string|url|max:500',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

    public function createCreateSocialMediaLinkDTO(): CreateSocialMediaLinkDTO
    {
        return new CreateSocialMediaLinkDTO(
            type: SocialMediaType::from($this->get('type')),
            link: $this->get('link'),
            icon: $this->file('icon'),
        );
    }
}
