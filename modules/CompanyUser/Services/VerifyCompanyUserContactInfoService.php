<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Shared\Media\Services\FileUploadService;
class VerifyCompanyUserContactInfoService
{
    public function __construct(
        private CompanyUserRepository $repository,
        private SendOtpEmail $sendOtpEmail
    )
    {

    }

    public function Verify($request)
    {

    }

}
