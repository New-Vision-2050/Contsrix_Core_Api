<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetJobOfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
