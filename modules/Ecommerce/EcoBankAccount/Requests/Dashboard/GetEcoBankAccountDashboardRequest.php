<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoBankAccountDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
