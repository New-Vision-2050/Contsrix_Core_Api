<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteWarehousRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
