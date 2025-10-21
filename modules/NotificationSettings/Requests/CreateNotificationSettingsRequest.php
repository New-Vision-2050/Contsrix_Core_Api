<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\NotificationSettings\DTO\CreateNotificationSettingsDTO;
use Modules\NotificationSettings\Models\NotificationSettings;

class CreateNotificationSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:' . implode(',', NotificationSettings::getTypeOptions()),
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'reminder_type' => 'required|string|in:' . implode(',', NotificationSettings::getReminderTypeOptions()),
            'message' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'user_id' => 'nullable|string|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Notification type is required',
            'type.in' => 'Notification type must be one of: mail, sms, both',
            'email.email' => 'Email must be a valid email address',
            'phone.max' => 'Phone number cannot exceed 20 characters',
            'reminder_type.required' => 'Reminder type is required',
            'reminder_type.in' => 'Reminder type must be one of: daily, weekly',
            'message.max' => 'Message cannot exceed 1000 characters',
            'user_id.uuid' => 'User ID must be a valid UUID',
            'user_id.exists' => 'User ID does not exist',
        ];
    }

    public function createCreateNotificationSettingsDTO(): CreateNotificationSettingsDTO
    {
        return new CreateNotificationSettingsDTO(
            type: $this->get('type'),
            email: $this->get('email'),
            phone: $this->get('phone'),
            reminderType: $this->get('reminder_type', 'daily'),
            message: $this->get('message'),
            isActive: (bool) $this->get('is_active', true),
            userId: $this->get('user_id') ? Uuid::fromString($this->get('user_id')) : null,
        );
    }
}
