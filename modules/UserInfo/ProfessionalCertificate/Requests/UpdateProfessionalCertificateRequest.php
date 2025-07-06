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
<<<<<<< HEAD
            'professional_bodie_id'=> 'required|string',
            'accreditation_name'=> 'required|string',
            'accreditation_number'=> 'required|string',
            'accreditation_degree'=> 'required|string',
            'date_obtain'=> 'required|date',
            'date_end'=> 'required|date',
            "file"=>"nullable|file",
=======
            'professional_bodie_id'=> 'nullable|string',
            'accreditation_name'=> 'nullable|string',
            'accreditation_number'=> 'nullable|string',
            'accreditation_degree'=> 'nullable|string',
            'date_obtain'=> 'nullable|date',
            'date_end'=> 'nullable|date',
            "file"=>"nullable",
>>>>>>> 4d33c9eb (merge roles with subscription)
        ];
    }
public function messages(): array
{
    return [
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
    public function createUpdateProfessionalCertificateCommand(): UpdateProfessionalCertificateCommand
    {
        return new UpdateProfessionalCertificateCommand(
            id: Uuid::fromString($this->route('id')),
            professional_bodie_id: $this->get('professional_bodie_id'),
            accreditation_name: $this->get('accreditation_name'),
            accreditation_number: $this->get('accreditation_number'),
            accreditation_degree: $this->get('accreditation_degree'),
            date_obtain: $this->get('date_obtain'),
            date_end: $this->get('date_end'),
<<<<<<< HEAD
            file: $this->file('file'),
=======
            file: $this->hasFile('file')?$this->file("file"):null,
>>>>>>> 4d33c9eb (merge roles with subscription)
        );
    }
}
