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

        if(isset($data['registration_type'])==1){
            // Validate classification_no
            if($this->repository->isClassificationExists($data['classification_no'])){
               $errors[] = [
                    'sentence' => 'رقم التصيف مقرر',
                    'sub_title' => '',
                    'status' => 0,
                    'validate' => 'change'
                ];
            } else {
                $errors[] = [
                    'sentence' => 'رقم التصيف مقرر',
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }elseif(isset($data['registration_type'])==2){
            // Validate registration_no
            if (isset($data['registration_no'])) {
                if (str_starts_with($data['registration_no'], '700') || str_starts_with($data['registration_no'], '40') || str_starts_with($data['registration_no'], '1')) {

                    $errors[] = [
                        'sentence' => 'رقم السجل التجاري صحيح',
                        'sub_title' => '',
                        'status' => 0,
                        'validate' => 'required'
                    ];
                } else {
                    $errors[] = [
                        'sentence' => 'رقم السجل التجاري صحيح',
                        'sub_title' => '',
                        'status' => 1,
                        'validate' => 'required'
                    ];
                }
            }

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
                    'sentence' => "رقم الهاتف موجود بالفعل.",
                    'sub_title' => 'phone',
                    'status' => 0,
                    'validate' => 'optional'
                ];
            }
            else if($validator->fails()){
                $errors[] = [
                    'sentence' => "رقم الهاتف غير صحيح.",
                    'sub_title' => 'phone',
                    'status' => 0,
                    'validate' => 'required'
                ];
            }
            else {
                $errors[] = [
                    'sentence' => "تم التحقق من رقم الهاتف بنجاح",
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
                    'sentence' => "البريد الإلكتروني موجود بالفعل.",
                    'sub_title' => 'email',
                    'status' => 0,
                    'validate' => 'optional',

                ];
            } else {
                $errors[] = [
                    'sentence' => "تم التحقق من البريد الإلكتروني بنجاح",
                    'sub_title' => '',
                    'status' => 1,
                    'validate' => 'required'
                ];
            }
        }


        return $errors;
    }
}
