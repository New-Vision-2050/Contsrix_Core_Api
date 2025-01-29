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

        // Laravel validation rules
        $rules = [
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|unique:companies,phone',
            'registration_no' => 'nullable|unique:company_registration_forms,registration_no',
        ];

        // Run validation
        $validator = Validator::make($request->all(), $rules);

        foreach ($rules as $field => $rule) {
            if ($validator->errors()->has($field)) {
                $errors[] = [
                    'sentence' => $validator->errors()->first($field),
                    'sub_title' => $field,
                    'status' => 0
                ];
            } else {
                $errors[] = [
                    'sentence' => "تم التحقق من {$field} بنجاح",
                    'sub_title' => $field,
                    'status' => 1
                ];
            }
        }
        return $errors;
    }
}
