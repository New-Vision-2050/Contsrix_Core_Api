<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class CompanyValidatedService
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function validate(Request $request): array
    {
        $errors = [
            'sentence' => '',
            'status' => 1,
        ];

        $data = $request->all();  // Get all the request data

        if (isset($data['user_name'])) {
            if ($this->repository->isUserNameExists($data['user_name']) || ! preg_match('/^[a-zA-Z0-9_]+$/', $data['user_name'])) {
                return [
                    'sentence' => __("validation.company_user_name"),
                    'status' => 0,
                ];
            }
        }

        if (isset($data['name'])) {
            if ($this->repository->isNameExists($data['name']) || ! preg_match('/^[\p{Arabic}\s]+$/u', $data['name'])) {
                return [
                    'sentence' => __("validation.company_name"),
                    'status' => 0,
                ];
            }
        }

        return $errors; 

    }
}
