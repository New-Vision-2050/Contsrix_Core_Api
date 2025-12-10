<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Social\Commands\UpdateSocialCommand;
use Modules\UserInfo\Social\Handlers\UpdateSocialHandler;

class UpdateSocialRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "whatsapp" => 'nullable',
            "facebook"=> 'nullable|url',
            "telegram"=> 'nullable',
            "instagram"=> 'nullable|url',
            "snapchat"=> 'nullable|url',
            "linkedin"=> 'nullable|url',
        ];
    }

    public function createUpdateSocialCommand(): UpdateSocialCommand
    {
        return new UpdateSocialCommand(
            id: Uuid::fromString($this->route('id')),
            whatsapp: $this->get("whatsapp"),
            facebook: $this->get("facebook"),
            telegram: $this->get("telegram"),
            instagram: $this->get("instagram"),
            snapchat: $this->get("snapchat"),
            linkedin: $this->get("linkedin"),
        );
    }
}
