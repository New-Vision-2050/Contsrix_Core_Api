<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\ProfessionalCertificate\DTO\CreateProfessionalCertificateDTO;

class CreateProfessionalCertificateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'=> 'required|string',
            'professional_bodie_id'=> 'required|string',
            'accreditation_name'=> 'required|string',
            'accreditation_number'=> 'required|string',
            'accreditation_degree'=> 'required|string',
            'date_obtain'=> 'required|date',
            'date_end'=> 'required|date',
        ];
    }

    public function createCreateProfessionalCertificateDTO(): CreateProfessionalCertificateDTO
    {
        return new CreateProfessionalCertificateDTO(
            company_id: '',
            global_id: '',
            professional_bodie_id: $this->get('professional_bodie_id'),
            accreditation_name: $this->get('accreditation_name'),
            accreditation_number: $this->get('accreditation_number'),
            accreditation_degree: $this->get('accreditation_degree'),
            date_obtain: $this->get('date_obtain'),
            date_end: $this->get('date_end'),
        );
    }
}
