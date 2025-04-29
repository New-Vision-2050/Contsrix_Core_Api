<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\UserInfo\BankAccount\Repositories\BankAccountRepository;
use Modules\UserInfo\Biography\Repositories\BiographyRepository;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;
use Modules\UserInfo\JobOffer\Repositories\JobOfferRepository;
use Modules\UserInfo\ProfessionalCertificate\Repositories\ProfessionalCertificateRepository;
use Modules\UserInfo\Qualification\Repositories\QualificationRepository;
use Modules\UserInfo\UserAbout\Repositories\UserAboutRepository;
use Modules\UserInfo\UserEducationalCourse\Repositories\UserEducationalCourseRepository;
use Modules\UserInfo\UserExperience\Repositories\UserExperienceRepository;
use Modules\UserInfo\UserPrivilege\Repositories\UserPrivilegeRepository;
use Modules\UserInfo\UserRelative\Repositories\UserRelativeRepository;
use Modules\UserInfo\UserSalary\Models\UserSalary;
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;

class CompanyUserDatatatusService
{
    protected $repository;

    public function __construct(
       private CompanyUserRepository $companyUserRepository,
       private EmploymentContractRepository $employmentContractRepository,
       private BankAccountRepository $bankAccountRepository,
       private UserSalaryRepository $userSalaryRepository,
       private UserAboutRepository $userAboutRepository,
       private UserRelativeRepository $userRelativeRepository,
       private QualificationRepository $qualificationRepository,
       private UserExperienceRepository $userExperienceRepository,
       private UserEducationalCourseRepository $userEducationalCourseRepository,
       private ProfessionalCertificateRepository $professionalCertificateRepository,
       private JobOfferRepository $jobOfferRepository,
       private UserPrivilegeRepository $userPrivilegeRepository
    )
    {
    }


    public function getDatatatus($companyId, $globalId)//: array
    {
        $companyUser = $this->companyUserRepository->getCompanyUserGlobalId($globalId);
        $employmentContract = $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);
        $userSalary = $this->userSalaryRepository->getUserSalary($companyId, $globalId);
        $bankAccounts = $this->bankAccountRepository->getBankAccountList($companyId, $globalId, 1);
        $userAbout = $this->userAboutRepository->getUserAbout($companyId, $globalId);
        $userRelative = $this->userRelativeRepository->getUserRelativeList($companyId, $globalId, 1);
        $qualification = $this->qualificationRepository->getQualificationList($companyId, $globalId, 1);
        $userExperience = $this->userExperienceRepository->getUserExperienceList($companyId, $globalId, 1);
        $UserEducationalCourse = $this->userEducationalCourseRepository->getUserEducationalCourseList($companyId, $globalId, 1);
        $professionalCertificate = $this->professionalCertificateRepository->getProfessionalCertificateList($companyId, $globalId, 1);
        $media = $companyUser->getFirstMedia('upload_biography');
        $jobOffer = $this->jobOfferRepository->getJobOffer($companyId, $globalId);
        $userPrivilege = $this->userPrivilegeRepository->getUserPrivilegeList($companyId, $globalId,1);

        $hasInfo = false;
        if(isset($userRelative['data']) && count($userRelative['data']) > 0){
            $infoFields = [
                'name', 'email', 'phone',
            ];
            $hasInfo = false;

            foreach ($infoFields as $field) {
                if (!empty($companyUser->{$field})) {
                    $hasInfo = true;
                }else{
                    if($field =='phone'){
                        if ($companyUser->users->first()->phone) {
                            $hasInfo = true;
                        }else{
                            $hasInfo = false;
                            break;
                        }
                    }
                }
            }
        }
        $hasContacts = false;
        $infoContacts = [
            'email', 'phone', 'other_phone','code_other_phone',
            'whatsapp', 'facebook', 'telegram', 'instagram', 'snapchat', 'linkedin'
        ];
        $hasContacts = false;

        foreach ($infoContacts as $field) {
            if (!empty($companyUser->{$field})) {
                $hasContacts = true;
            }else{
                if($field =='phone'){
                    if ($companyUser->users->first()->phone) {
                        $hasInfo = true;
                    }else{
                        $hasInfo = false;
                        break;
                    }
                }
            }
        }

        $hasAddress = false;
        $infoAddress = [
            'address', 'postal_code',
        ];
        $hasAddress = false;

        foreach ($infoAddress as $field) {
            if (!empty($companyUser->{$field})) {
                $hasAddress = true;
            }else{
                $hasAddress = false;
                break;
            }
        }


        $hasIdentity = false;
        $infoIdentity = [
            "passport",
            "passport_start_date",
            "passport_end_date",
            // "identity",
            // "identity_start_date",
            // "identity_end_date",
            "border_number",
            "border_number_start_date",
            "border_number_end_date",
            "entry_number",
            "entry_number_start_date",
            "entry_number_end_date",
            "work_permit",
            "work_permit_start_date",
            "work_permit_end_date",
        ];
        $hasIdentity = false;

        foreach ($infoIdentity as $field) {
            if (!empty($companyUser->{$field})) {
                $hasIdentity = true;
            }else{
                $hasIdentity = false;
                break;
            }
        }


        return [
            'info_company_user' => $hasInfo,
            'bank_account' => isset($bankAccounts['data']) && count($bankAccounts['data']) > 0,
            'user_about' => !empty($userAbout),
            'contact_info' => $hasContacts,
            'address_info' => $hasAddress,
            'identity_info' => $hasIdentity,
            'qualification' => isset($qualification['data']) && count($qualification['data']) > 0,
            'experience'  => isset($userExperience['data']) && count($userExperience['data']) > 0,
            'educational_course' => isset($UserEducationalCourse['data']) && count($UserEducationalCourse['data']) > 0,
            'professional_certificate' => isset($professionalCertificate['data']) && count($professionalCertificate['data']) > 0,
            'biography' => $media ? true : false,
            'jobOffer' => !empty($jobOffer),
            'employment_contract' => !empty($employmentContract),
            'user_salary' =>  !empty($userSalary),
            'userPrivilege' => isset($userPrivilege['data']) && count($userPrivilege['data']) > 0,
        ];
    }

}
