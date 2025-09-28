<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteNotificationSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
