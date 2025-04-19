<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyUserValidationService
{

    private $errors = [];
    public function __construct(
        private CompanyUserRepository $repository,
    )
    {

    }

    public function validateName()
    {
        if(!preg_match("/\p{Arabic}/u", request()->name) ||count(explode(" ", request()->name)) <3){
            $this->errors[] =   [
                'sentence' => __("validation.user-name"),
                'sub_title' => '',
                'status' => 0,
                'validate' => 'required'
            ];
        }else{
            $this->errors[] =   [
                'sentence' => __("validation.user-name"),
                'sub_title' => '',
                'status' => 1,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    public function validateEmail()
    {
        if($user = $this->repository->findByEmail(request()->email)) {
            $this->errors[] = [
                'sentence' => __("validation.user-email-error",["name"=>$user->name]),
                'sub_title' => 'email',
                'status' => 0,
                'validate' => 'required'
            ];
        }else{
            $this->errors[] = [
                'sentence' => __("validation.user-email-success"),
                'sub_title' => 'email',
                'status' => 1,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    public function validatePhone()
    {
        $validator = Validator::make(request()->all(), ['phone'=>'required|phone']);
        if($this->repository->findByPhone(request()->phone)) {
            $this->errors[] = [
                'sentence' => __("validation.user-phone-error"),
                'sub_title' => 'phone',
                'status' => 0,
                'validate' => 'required'
            ];
        }
        else if($validator->fails()){
            $this->errors[] = [
                'sentence' => $validator->errors()->messages()["phone"][0],
                'sub_title' => 'phone',
                'status' => 0,
                'validate' => 'required'
            ];
        }
        else{
            $this->errors[] = [
                'sentence' => __("validation.user-phone-success"),
                'sub_title' => 'phone',
                'status' => 1,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    public function get()
    {
        return $this->errors;
    }


}
