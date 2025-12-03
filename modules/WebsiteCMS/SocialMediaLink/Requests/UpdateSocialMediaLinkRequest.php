<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\SocialMediaLink\Commands\UpdateSocialMediaLinkCommand;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;

class UpdateSocialMediaLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(SocialMediaType::values())],
            'link' => 'required|string|url|max:500',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

    public function createUpdateSocialMediaLinkCommand(): UpdateSocialMediaLinkCommand
    {
        return new UpdateSocialMediaLinkCommand(
            id: Uuid::fromString($this->route('id')),
            type: SocialMediaType::from($this->get('type')),
            link: $this->get('link'),
            icon: $this->file('icon'),
        );
    }
}
