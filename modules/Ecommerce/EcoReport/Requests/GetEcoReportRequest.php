<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
