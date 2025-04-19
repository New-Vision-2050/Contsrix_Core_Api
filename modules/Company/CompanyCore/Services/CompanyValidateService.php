<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class CompanyValidateService
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function validate(Request $request): array
    {
        $errors = [];
        $data = $request->all();  // Get all the request data



        if (isset($data['user_name'])) {
            if (!$this->repository->isUserNameExists($data['user_name']) && preg_match('/^[a-zA-Z0-9_]+$/', $data['user_name'])) {
                $errors[] = [
                    'sentence' => __("validation.company_name"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            } else {
                $errors[] = [
                    'sentence' => __("validation.company_name"),
                    'sub_title' => '',
                    'status' => 0,
                    'validate' => 'change'
                ];
            }
        }

        if (isset($data['registration_type']) && $data['registration_type'] == 2) {
            // Validate registration_no
            if ($this->repository->isRegistrationExists($data['registration_no'], $data['registration_type_id'])) {
                $errors[] = [
                    'sentence' => __("validation.classification_number_available"),
                    'sub_title' => '',
                    'status' => 0,
                    'validate' => 'change'
                ];
            } else {
                $errors[] = [
                    'sentence' => __("validation.classification_number_available"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        } elseif (isset($data['registration_type']) && $data['registration_type'] == 1) {
            // Validate registration_no
            if (
                str_starts_with($data['registration_no'], '700') ||
                str_starts_with($data['registration_no'], '40') ||
                str_starts_with($data['registration_no'], '1')
            ) {
                $errors[] = [
                    'sentence' => __("validation.commercial_registration_number"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            } else {
                $errors[] = [
                    'sentence' => __("validation.commercial_registration_number"),
                    'sub_title' => '',
                    'status' => 0,
                    'validate' => 'required'
                ];
            }

            if ($this->repository->isRegistrationExists($data['registration_no'], $data['registration_type_id'])) {
                $errors[] = [
                    'sentence' => __("validation.commercial_registration_number_with_another"),
                    'sub_title' => 'registration_no',
                    'status' => 0,
                    'validate' => 'optional'
                ];
            } else {
                $errors[] = [
                    'sentence' =>__("validation.commercial_registration_number_with_another"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }


        // Validate phone
        if (isset($data['phone'])) {
            $validator = Validator::make($request->all(), ['phone'=>'required|phone']);

            if ($this->repository->isPhoneExists($data['phone'])) {
                $errors[] = [
                    'sentence' => __("validation.phone_number_already_exists"),
                    'sub_title' => 'phone',
                    'status' => 0,
                    'validate' => 'optional'
                ];
            }
            else if($validator->fails()){
                $errors[] = [
                    'sentence' => __("validation.invalid_phone_number"),
                    'sub_title' => 'phone',
                    'status' => 0,
                    'validate' => 'required'
                ];
            }
            else {
                $errors[] = [
                    'sentence' => __("validation.phone_number_verified_successfully"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }

        // Validate email
        if (isset($data['email'])) {
            if ($this->repository->isEmailExists($data['email'])) {
                $errors[] = [
                    'sentence' => __('validation.email_already_exists'),
                    'sub_title' => 'email',
                    'status' => 0,
                    'validate' => 'optional',

                ];
            } else {
                $errors[] = [
                    'sentence' => __("validation.email_verified_successfully"),
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }


        return $errors;
    }
}
