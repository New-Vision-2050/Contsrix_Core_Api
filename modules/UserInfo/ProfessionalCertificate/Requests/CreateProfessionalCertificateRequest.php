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
            'professional_bodie_id'=> 'nullable|string',
            'accreditation_name'=> 'nullable|string',
            'accreditation_number'=> 'nullable|string',
            'accreditation_degree'=> 'nullable|string',
            'date_obtain'=> 'nullable|date',
            'date_end'=> 'nullable|date',
            "file"=>"nullable|file",
        ];
    }
    public function messages(): array
    {
        return [
            'user_id.required' => __('validation.user_id_required'),
            'professional_bodie_id.required' => __('validation.professional_bodie_id_required'),
            'accreditation_name.required' => __('validation.accreditation_name_required'),
            'accreditation_number.required' => __('validation.accreditation_number_required'),
            'accreditation_degree.required' => __('validation.accreditation_degree_required'),
            'date_obtain.required' => __('validation.date_obtain_required'),
            'date_obtain.date' => __('validation.date_obtain_date'),
            'date_end.required' => __('validation.date_end_required'),
            'date_end.date' => __('validation.date_end_date'),
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
            file: $this->file('file'),
        );
    }
}
