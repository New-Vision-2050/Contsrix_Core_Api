<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forum\Enums\ForumMessageType;

class SendForumMessageRequest extends FormRequest
{
    public function rules(): array
    {
        $type = $this->input('type', ForumMessageType::TEXT->value);

        return [
            'type' => ['sometimes', Rule::enum(ForumMessageType::class)],
            'body' => [
                Rule::requiredIf($type === ForumMessageType::TEXT->value),
                'nullable',
                'string',
                'max:5000',
            ],
            'attachment' => match ($type) {
                ForumMessageType::IMAGE->value => ['required', 'image', 'max:10240'],
                ForumMessageType::VOICE->value => ['required', 'file', 'mimetypes:audio/*,video/mp4,video/webm,application/ogg', 'max:20480'],
                ForumMessageType::FILE->value => ['required', 'file', 'max:20480'],
                default => ['prohibited'],
            },
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }

    public function messageType(): ForumMessageType
    {
        return ForumMessageType::from($this->input('type', ForumMessageType::TEXT->value));
    }

    public function durationSeconds(): ?int
    {
        return $this->filled('duration_seconds') ? (int) $this->input('duration_seconds') : null;
    }
}
