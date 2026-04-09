<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\ProfessionalCertificate\Commands\UpdateProfessionalCertificateCommand;
use Modules\UserInfo\ProfessionalCertificate\Handlers\UpdateProfessionalCertificateHandler;

class UpdateProfessionalCertificateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'professional_bodie_id'=> 'nullable|string',
            'accreditation_name'=> 'nullable|string',
            'accreditation_number'=> 'nullable|string',
            'professional_degree_id'=> 'nullable|integer|exists:professional_degrees,id',
            'date_obtain'=> 'nullable|date',
            'date_end'=> 'nullable|date',
            "file"=>"nullable",
        ];
    }
public function messages(): array
{
    return [
        'professional_bodie_id.required' => __('validation.professional_bodie_id_required'),
        'accreditation_name.required' => __('validation.accreditation_name_required'),
        'accreditation_number.required' => __('validation.accreditation_number_required'),
        'professional_degree_id.integer' => __('validation.professional_degree_id_integer'),
        'professional_degree_id.exists' => __('validation.professional_degree_id_exists'),
        'date_obtain.required' => __('validation.date_obtain_required'),
        'date_obtain.date' => __('validation.date_obtain_date'),
        'date_end.required' => __('validation.date_end_required'),
        'date_end.date' => __('validation.date_end_date'),
    ];
}
    public function createUpdateProfessionalCertificateCommand(): UpdateProfessionalCertificateCommand
    {
        return new UpdateProfessionalCertificateCommand(
            id: Uuid::fromString($this->route('id')),
            professional_bodie_id: $this->get('professional_bodie_id'),
            accreditation_name: $this->get('accreditation_name'),
            accreditation_number: $this->get('accreditation_number'),
            professional_degree_id: $this->get('professional_degree_id') ? (int)$this->get('professional_degree_id') : null,
            date_obtain: $this->get('date_obtain'),
            date_end: $this->get('date_end'),
            file: $this->hasFile('file')?$this->file("file"):null,
        );
    }
}
