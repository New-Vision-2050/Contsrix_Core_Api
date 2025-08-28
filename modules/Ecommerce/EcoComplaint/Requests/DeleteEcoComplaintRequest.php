<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
