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
            'professional_bodie_id'=> 'required|string',
            'accreditation_name'=> 'required|string',
            'accreditation_number'=> 'required|string',
            'accreditation_degree'=> 'required|string',
            'date_obtain'=> 'required|date',
            'date_end'=> 'required|date',
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
        );
    }
}
