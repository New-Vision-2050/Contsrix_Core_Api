<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserExperience\DTO\CreateUserExperienceDTO;

class CreateUserExperienceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'job_name' => 'required|string',
            'training_from' => 'required|date',
            'training_to' => 'required|date',
            'company_name' => 'required|string',
            'about' => 'required|string',

        ];
    }

    public function createCreateUserExperienceDTO(): CreateUserExperienceDTO
    {
        return new CreateUserExperienceDTO(
            company_id:'',
            global_id:'',
            job_name: $this->get('job_name'),
            training_from : $this->get('training_from'),
            training_to: $this->get('training_to'),
            company_name: $this->get('company_name'),
            about: $this->get('about'),
        );
    }
}
