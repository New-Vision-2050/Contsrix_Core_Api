<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserExperience\Commands\UpdateUserExperienceCommand;
use Modules\UserInfo\UserExperience\Handlers\UpdateUserExperienceHandler;

class UpdateUserExperienceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'job_name' => 'required|string',
            'training_from' => 'required|date',
            'training_to' => 'required|date',
            'company_name' => 'required|string',
            'about' => 'required|string',
        ];
    }

    public function createUpdateUserExperienceCommand(): UpdateUserExperienceCommand
    {
        return new UpdateUserExperienceCommand(
            id: Uuid::fromString($this->route('id')),
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
