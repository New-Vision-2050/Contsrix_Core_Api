<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

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
use Modules\UserInfo\UserSalary\Repositories\UserSalaryRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\UserInfo\Contactinfo\Repositories\ContactinfoRepository;
use Modules\UserInfo\UserProfessionalData\Repositories\UserProfessionalDataRepository;
use Modules\UserInfo\UserProfessionalData\Requests\GetUserProfessionalDataRequest;

class CompanyUserDatatatusService
{
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
        private UserPrivilegeRepository $userPrivilegeRepository,
        private CompanyRepository $companyRepository,
        private ContactinfoRepository $contactinfoRepository,
        private UserProfessionalDataRepository $userProfessionalDataRepository
    ) {}

    public function getDatatatus($companyId, $globalId): array
    {
        $companyUser = $this->companyUserRepository->getCompanyUserGlobalId($globalId);
        $company = $this->companyRepository->getCompany($companyId);

        $employmentContract = $this->employmentContractRepository->getEmploymentContract($companyId, $globalId);
        $userSalary = $this->userSalaryRepository->getUserSalary($companyId, $globalId);
        $bankAccounts = $this->bankAccountRepository->getBankAccountList($companyId, $globalId, 1);
        $userAbout = $this->userAboutRepository->getUserAbout($companyId, $globalId);
        $userRelatives = $this->userRelativeRepository->getUserRelativeList($companyId, $globalId, 1);
        $qualifications = $this->qualificationRepository->getQualificationList($companyId, $globalId, 1);
        $experiences = $this->userExperienceRepository->getUserExperienceList($companyId, $globalId, 1);
        $courses = $this->userEducationalCourseRepository->getUserEducationalCourseList($companyId, $globalId, 1);
        $certificates = $this->professionalCertificateRepository->getProfessionalCertificateList($companyId, $globalId, 1);
        $media = $companyUser->getFirstMedia('upload_biography');
        $jobOffer = $this->jobOfferRepository->getJobOffer($companyId, $globalId);
        $userPrivileges = $this->userPrivilegeRepository->getUserPrivilegeList($companyId, $globalId, 1);
        $userDataContactInfo = $this->contactinfoRepository->getContactinfo($companyId, $globalId);
        $userProfessionalData = $this->userProfessionalDataRepository->getUserProfessionalData( $globalId , $companyId);

        $hasIdentity = $this->hasIdentityInfo($companyUser, $company->country_id);
        $hasIdentityInfoUser = $this->hasIdentityInfoUser($companyUser, $company->country_id);
        $hasBasicInfo = $this->hasBasicUserInfo($companyUser);
        $hasContactInfo = $this->hasContactInfo($companyUser, $userRelatives,$userDataContactInfo);
        $hasAddressInfo = $this->hasFilledFields($userDataContactInfo, ['address', 'postal_code']);

        return [
            'info_company_user' => $hasBasicInfo && $hasIdentityInfoUser,
            'bank_account' => $this->hasData($bankAccounts),
            'user_about' => !empty($userAbout),
            'contact_info' => $hasContactInfo,
            'address_info' => $hasAddressInfo,
            'identity_info' => $hasIdentity,
            'qualification' => $this->hasData($qualifications),
            'experience' => $this->hasData($experiences),
            'educational_course' => $this->hasData($courses),
            'professional_certificate' => $this->hasData($certificates),
            'biography' => !empty($media),
            'jobOffer' => !empty($jobOffer),
            'employment_contract' => !empty($employmentContract) && !empty($jobOffer),
            'user_salary' => !empty($userSalary),
            'userPrivilege' => $this->hasData($userPrivileges),
            'user_professional_data' => !empty($userProfessionalData),
        ];
    }

    private function hasData($response): bool
    {
        return isset($response['data']) && count($response['data']) > 0;
    }

private function hasFilledFields(?object $object, array $fields): bool
{
    if (!$object) return false;

    foreach ($fields as $field) {
        if (empty($object->{$field})) {
            return false;
        }
    }
    return true;
}

    private function hasIdentityInfo(object $companyUser, $companyCountryId): bool
    {
        $identityFields = $companyUser->country_id == $companyCountryId
            ? ['identity', 'identity_start_date', 'identity_end_date']
            : [
                'passport', 'passport_start_date', 'passport_end_date',
                'border_number', 'border_number_start_date',
                'entry_number', 'entry_number_start_date', 'entry_number_end_date',
                'work_permit', 'work_permit_start_date', 'work_permit_end_date',
            ];

        return $this->hasFilledFields($companyUser, $identityFields);
    }

    private function hasIdentityInfoUser(object $companyUser, $companyCountryId): bool
    {
        $identityFields = $companyUser->country_id == $companyCountryId
            ? ['identity', 'identity_start_date', 'identity_end_date']
            : [
                'passport', 'passport_start_date', 'passport_end_date',
            ];

        return $this->hasFilledFields($companyUser, $identityFields);
    }

    private function hasBasicUserInfo(object $companyUser): bool
    {
        $fields = ['name', 'email', 'phone', 'gender', 'country_id', 'birthdate_gregorian', 'birthdate_hijri'];

        foreach ($fields as $field) {
            if (!empty($companyUser->{$field})) {
                continue;
            }

            if ($field === 'phone' && $companyUser->users->first()?->phone) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function hasContactInfo(object $companyUser, array $userRelatives,$userDataContactInfo): bool
    {
        if (!isset($userRelatives['data']) || count($userRelatives['data']) === 0) {
            return false;
        }

        $socialFields = [
             'whatsapp'
        ];

        foreach ($socialFields as $field) {
            if (empty($companyUser->{$field})) {
                return false;
            }
        }

        $userDataContactFields = [
            'email', 'phone', 'other_phone', 'code_other_phone',
            'address', 'postal_code'
        ];

        foreach ($userDataContactFields as $field) {
            if (empty($userDataContactInfo->{$field})) {
                return false;
            }
        }

        return true;
    }
}
