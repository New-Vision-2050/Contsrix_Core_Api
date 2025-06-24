<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteProgramSystemRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
