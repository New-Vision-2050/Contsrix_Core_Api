<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryCurrencyPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Presenters\UserPresenter;
use Modules\UserInfo\BankAccount\Presenters\BankAccountPresenter;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\UserProfessionalData\Presenters\UserProfessionalDataPresenter;

class CompanyUserClientPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;
    private ?string $userId;
    private ?int $role;

    public function __construct(CompanyUser $companyUser, string $userId = null, ?int $role = null)
    {
        $this->companyUser = $companyUser;
        $this->userId = $userId;
        $this->role   = $role;
    }

    public static function collection(iterable $collection, ...$additionalParams): array
    {
        $role = $additionalParams[0] ?? null;

        return collect($collection)->map(function ($item) use ($role) {
            return (new self($item, null, $role))->getData();
        })->toArray();
    }

    private function resolveStatus(): mixed
    {
        if ($this->role === null) {
            return null;
        }

        $roleStr = (string) $this->role;

        foreach ($this->companyUser->users as $user) {
            $record = $user->companyUserCompanies
                ->first(fn($c) => $c->getRawOriginal('role') === $roleStr);

            if ($record) {
                return (int) $record->getRawOriginal('status');
            }
        }

        return null;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyUser->id,
            'global_id' => $this->companyUser->global_id,
            'user_id' => $this->companyUser->users()->where("company_id",tenant("id"))->first()?->id,
            'name' => $this->companyUser->name,
            'email' => $this->companyUser->email,
            "residence" => $this->companyUser->residence,
            "passport" => $this->companyUser->passport,
            "identity" => $this->companyUser->identity,
            "border_number" => $this->companyUser->border_number,
            "phone" => $this->companyUser->users()->first()->phone,
            'job_title_id'=>$this->companyUser?->userProfessionalData?->job_title_id,
            "job_title" => $this->companyUser?->userProfessionalData?->jobTitle?->name,
            "country" => $this->companyUser?->country ? (new CountryPresenter($this->companyUser?->country))->getData() : collect([]),
            'status' => $this->resolveStatus(),
            'data_status' => 0,
            "company" => ($this->companyUser->companies->unique('id')->first())
                ? (new CompanyUsersPresenter(
                    $this->companyUser->companies->unique('id')->first(),
                    $this->companyUser
                ))->getData()
                : null,
            "companies" => CompanyUsersPresenter::collection($this->companyUser->clientCompanies->unique('id'),$this->companyUser),
            'Job_role' => '-',
            'date_appointment' => '-',
            'branch'=>$this->companyUser->userProfessionalData?->branch != null ? $this->companyUser->userProfessionalData?->branch?->name :"-" ,
            'other_phone'=> $this->companyUser->other_phone??'-',
            'code_other_phone' => $this->companyUser->code_other_phone,
            'address' => $this->companyUser->address??'-',
            'address_attendance' =>  $this->companyUser->address_attendance??'-',
            'image_url' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'bank_account' => $this->companyUser->bankAccount ? (new BankAccountPresenter($this->companyUser->bankAccount))->getData() : null,
            'user_professional_data' => $this->companyUser->userProfessionalData ? (new UserProfessionalDataPresenter($this->companyUser->userProfessionalData))->getData():null,
            "currency"=> $this->companyUser->currency?(new CountryCurrencyPresenter($this->companyUser->currency))->getData():null,

            // Extended fields
            'phone_code' => $this->companyUser->users->first()?->phone_code,
            'nickname' => $this->companyUser->nickname,
            'birthdate_gregorian' => $this->companyUser->birthdate_gregorian,
            'birthdate_hijri' => $this->companyUser->birthdate_hijri,
            'nationality' => $this->companyUser->country?->name,
            'postal_code' => $this->companyUser->postal_code,
            'landline_number' => $this->companyUser->landline_number,
            'management' => $this->companyUser->userProfessionalData?->management?->name,
            'department' => $this->companyUser->userProfessionalData?->department?->name,
            'job_type' => $this->companyUser->userProfessionalData?->jobType?->name,
            'job_code' => $this->companyUser->userProfessionalData?->job_code,
            'attendance_constraint' => $this->companyUser->userProfessionalData?->attendanceConstraint?->constraint_name,
            'whatsapp' => $this->companyUser->whatsapp,
            'linkedin' => $this->companyUser->linkedin,
            'facebook' => $this->companyUser->facebook,
            'instagram' => $this->companyUser->instagram,
            'telegram' => $this->companyUser->telegram,
            'snapchat' => $this->companyUser->snapchat,
            'time_zone' => $this->companyUser->timeZone?->name,
            'language' => $this->companyUser->language?->name,
            'iban' => $this->companyUser->bankAccount?->iban,
            'contract_number' => $this->companyUser->employmentContract?->contract_number,
            'contract_start_date' => $this->companyUser->employmentContract?->start_date,
            'notice_period' => $this->companyUser->employmentContract?->notice_period,
            'salary' => $this->companyUser->userSalary?->salary,
            'bank-info' => $this->companyUser->bankAccount?->iban,
            'salary-info' => $this->companyUser->userSalary?->salary,
            'employment-info' => $this->companyUser->userProfessionalData?->jobTitle?->name,
            'contact-info' => $this->companyUser->contactInfo?->phone,
            'social-media' => implode(', ', array_keys(array_filter([
                'WhatsApp' => $this->companyUser->whatsapp,
                'LinkedIn' => $this->companyUser->linkedin,
                'Facebook' => $this->companyUser->facebook,
                'Instagram' => $this->companyUser->instagram,
                'Telegram' => $this->companyUser->telegram,
                'Snapchat' => $this->companyUser->snapchat,
            ]))) ?: null,
            'family-info' => $this->companyUser->userRelatives->count() ?: null,
            'about-me' => $this->companyUser->userAbout?->about_me,
            'cv' => $this->companyUser->getFirstMedia('upload_biography') !== null,
            'certificates' => $this->companyUser->professionalCertificates->count() ?: null,
            'qualification' => $this->companyUser->qualifications->count() ?: null,
            'experience' => $this->companyUser->userExperiences->count() ?: null,
            'courses' => $this->companyUser->userEducationalCourses->count() ?: null,
            'work-license' => $this->companyUser->work_permit ?: null,
            'privileges' => $this->companyUser->userPrivileges->count() ?: null,
            'official-data' => ($cr = $this->companyUser->contractualRelationships->first())
                ? ($cr->employment_name ?? $cr->registration_number)
                : null,
            'job-offer' => $this->companyUser->jobOffer?->job_offer_number,
            'contract-work' => $this->companyUser->employmentContract?->contract_number,
            'contract_duration' => $this->companyUser->employmentContract?->contract_duration,
            'education' => $this->companyUser->qualifications->count() ?: null,
            'passport-info' => $this->companyUser->passport ?: null,
            'residence-info' => $this->companyUser->residence ?: null,
            'number_of_projects' => null,
            'end_date' => null,
            'broker' => null,

        ];
    }
}
