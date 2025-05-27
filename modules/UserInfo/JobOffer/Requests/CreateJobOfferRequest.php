<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\JobOffer\DTO\CreateJobOfferDTO;

class CreateJobOfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'job_offer_number' => 'required|string',
            'date_send' => 'required|string',
            'date_accept' => 'required|string',
            'file' => 'nullable|array',
            'file.*' => 'mimes:pdf,jpeg,jpg,png,doc,docx',
        ];
    }
    public function messages(): array
    {
        return [
            'user_id.required' => __('validation.user_id_required'),
            'job_offer_number.required' => __('validation.job_offer_number_required'),
            'date_send.required' => __('validation.date_send_required'),
            'date_accept.required' => __('validation.date_accept_required'),
            'file.*.mimes' => __('validation.company_legal.file_mimes'),
        ];
    }
    public function createCreateJobOfferDTO(): CreateJobOfferDTO
    {
        return new CreateJobOfferDTO(
            company_id: '',
            global_id: '',
            job_offer_number: $this->get('job_offer_number'),
            date_send: $this->get('date_send'),
            date_accept: $this->get('date_accept'),
        );
    }
}
