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
<<<<<<< HEAD
            "whatsapp" => 'nullable',
            "facebook"=> 'nullable|url',
            "telegram"=> 'nullable|url',
            "instagram"=> 'nullable|url',
            "snapchat"=> 'nullable|url',
            "linkedin"=> 'nullable|url',
=======
            "whatsapp" => 'nullable|string',
            "facebook"=> 'nullable|string',
            "telegram"=> 'nullable|string',
            "instagram"=> 'nullable|string',
            "snapchat"=> 'nullable|string',
            "linkedin"=> 'nullable|string',
>>>>>>> 7be6c72c (merge with stage (first version ))
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
