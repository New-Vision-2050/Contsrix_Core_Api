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
            'training_to' => 'required|date|after_or_equal:training_from',
            'company_name' => 'required|string',
            'about' => 'required|string',
        ];
    }
    public function messages(): array
    {
        return [
            'job_name.required' => __('validation.job_name_required'),
            'training_from.required' => __('validation.training_from_required'),
            'training_to.required' => __('validation.training_to_required'),
            'training_to.after_or_equal' => __('validation.training_to_after_from'),
            'company_name.required' => __('validation.company_name_required'),
            'about.required' => __('validation.about_required'),
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
