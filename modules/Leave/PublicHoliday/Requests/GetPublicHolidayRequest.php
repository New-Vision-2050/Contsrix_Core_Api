<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetPublicHolidayRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
