<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteProfessionalCertificateRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
