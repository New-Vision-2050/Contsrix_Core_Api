<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteTermAndCondition\DTO\CreateWebsiteTermAndConditionDTO;

class CreateWebsiteTermAndConditionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateWebsiteTermAndConditionDTO(): CreateWebsiteTermAndConditionDTO
    {
        return new CreateWebsiteTermAndConditionDTO(
            name: $this->get('name'),
        );
    }
}
