<?php

declare(strict_types=1);

namespace Modules\Company\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\Repositories\CompanyRepository;

class CompanyValidateService
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function validate($request): array
    {
        $errors = [];
        $data = $request->all();  // Get all the request data

        // Validate registration_no
        if (isset($data['registration_no'])) {
            if (!str_starts_with((string) $data['registration_no'], '700')) {
                $errors[] = [
                    'sentence' => 'رقم السجل التجاري صحيح',
                    'sub_title' => 'registration_no',
                    'status' => 0,
                    'validate' => 'required'
                ];
            } else {
                $errors[] = [
                    'sentence' => 'رقم السجل التجاري صحيح',
                    'sub_title' => 'registration_no',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }

        // Validate phone
        if (isset($data['phone'])) {
            if ($this->repository->isPhoneExists($data['phone'])) {
                $errors[] = [
                    'sentence' => "رقم الهاتف موجود بالفعل.",
                    'sub_title' => 'phone',
                    'status' => 0,
                    'validate' => 'optional'
                ];
            } else {
                $errors[] = [
                    'sentence' => "تم التحقق من رقم الهاتف بنجاح",
                    'sub_title' => 'phone',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }

        // Validate email
        if (isset($data['email'])) {
            if ($this->repository->isEmailExists($data['email'])) {
                $errors[] = [
                    'sentence' => "البريد الإلكتروني موجود بالفعل.",
                    'sub_title' => 'email',
                    'status' => 0,
                    'validate' => 'optional',

                ];
            } else {
                $errors[] = [
                    'sentence' => "تم التحقق من البريد الإلكتروني بنجاح",
                    'sub_title' => 'email',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }

        if (isset($data['registration_no'])) {
            if ($this->repository->isRegistrationExists($data['registration_no'])) {
                $errors[] = [
                    'sentence' => 'رقم السجل التجاري مع رقم ترخيص اخر',
                    'sub_title' => 'registration_no',
                    'status' => 0,
                    'validate' => 'optional'
                ];
            } else {
                $errors[] = [
                    'sentence' => 'رقم السجل التجاري مع رقم ترخيص اخر',
                    'sub_title' => 'registration_no',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }

        return $errors;
    }
}
