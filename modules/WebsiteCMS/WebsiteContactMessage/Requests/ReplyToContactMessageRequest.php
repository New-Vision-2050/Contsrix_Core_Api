<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteContactMessage\DTO\ReplyToContactMessageDTO;

class ReplyToContactMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|integer|in:0,1',
            'reply_message' => 'required|string',
        ];
    }

    public function createReplyToContactMessageDTO(): ReplyToContactMessageDTO
    {
        return new ReplyToContactMessageDTO(
            id: Uuid::fromString($this->route('id')),
            status:(int) $this->get('status'),
            replyMessage: $this->get('reply_message'),
        );
    }
}
