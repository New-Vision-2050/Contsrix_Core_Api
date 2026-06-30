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

class CompanyUserPresenter extends AbstractPresenter
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
    private function calculateContractEndDate(): ?string
    {
        $contract = $this->companyUser->employmentContract;

        if (!$contract || !$contract->start_date || !$contract->contract_duration) {
            return null;
        }

        $startDate = $contract->start_date;
        // لو start_date كان Carbon object ناخد منه النص
        if ($startDate instanceof \Carbon\Carbon) {
            $startDate = $startDate->format('Y-m-d');
        }

        $duration = (int) $contract->contract_duration;
        if ($duration <= 0) {
            return null;
        }

        // ✅ أضف "years" لو المدة بالسنوات، أو "months" لو بالشهور
        $endTimestamp = strtotime("+{$duration} years", strtotime($startDate));
        if ($endTimestamp === false) {
            return null;
        }

        return date('Y-m-d', $endTimestamp);
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
    private function generateWhatsAppUrl(): ?string
    {
        $rawNumber = $this->companyUser->whatsapp;
        if (!$rawNumber) {
            return null;
        }

        $cleanNumber = preg_replace('/[^0-9]/', '', $rawNumber);

        // if (strlen($cleanNumber) <= 10) {
        //     $phoneCode = $this->companyUser->users->first()?->phone_code;
        //     if ($phoneCode) {
        //         $cleanCode = preg_replace('/[^0-9]/', '', $phoneCode);
        //         $cleanNumber = $cleanCode . $cleanNumber;
        //     }
        // }

        return 'https://wa.me/' . $cleanNumber;
    }

    private function presentAttendanceConstraints(CompanyUser $companyUser): array
    {
        $constraints = [];
        $primary = $companyUser->userProfessionalData?->attendanceConstraint;
        if ($primary) {
            $constraints[] = [
                'id'   => $primary->id,
                'name' => $primary->constraint_name,
                'type' => 'primary',
            ];
        }

        $users = $companyUser->users()->with('additionalAttendanceConstraints')->get();
        foreach ($users as $user) {
            foreach ($user->additionalAttendanceConstraints as $constraint) {
                $constraints[] = [
                    'id'   => $constraint->id,
                    'name' => $constraint->constraint_name,
                    'type' => 'additional',
                ];
            }
        }

        return $constraints;
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
            "entry_number" => $this->companyUser->entry_number ?? '-',
            "identity_residence" => $this->companyUser->identity . '/' . $this->companyUser->residence,
            "identity_entry" => $this->companyUser->identity . '/' . $this->companyUser->entry_number,
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
//            "client_companies" => CompanyUsersPresenter::collection($this->companyUser->clientCompanies->unique('id'),$this->companyUser),
            "companies" => CompanyUsersPresenter::collection($this->companyUser->companies->unique('id'),$this->companyUser),
            'Job_role' => '-',
            'date_appointment' => '-',
            'branch'=>$this->companyUser->userProfessionalData?->branch != null ? $this->companyUser->userProfessionalData?->branch?->name :"-" ,
            'other_phone'=> $this->companyUser->other_phone??'-',
            'code_other_phone' => $this->companyUser->code_other_phone,
            'address' => $this->companyUser->address??'-',
            'address_attendance' =>  $this->companyUser->address_attendance??'-',
            'image_url' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'bank_account' => $this->companyUser->bankAccount ? (new BankAccountPresenter($this->companyUser->bankAccount))->getData() : null,
            // 'bank_account' => $this->companyUser->bankAccount ? (new BankAccountPresenter($this->companyUser->bankAccount))->getData() : null,
            'user_professional_data' => $this->companyUser->userProfessionalData ? (new UserProfessionalDataPresenter($this->companyUser->userProfessionalData))->getData():null,
            "currency"=> $this->companyUser->currency?(new CountryCurrencyPresenter($this->companyUser->currency))->getData():null,
            "photo"=>$this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),

            // Extended fields
            'phone_code' => $this->companyUser->users->first()?->phone_code,
            'nickname' => $this->companyUser->nickname,
            'birthdate_gregorian' => $this->companyUser->birthdate_gregorian
                ? \Carbon\Carbon::parse($this->companyUser->birthdate_gregorian)->format('Y-m-d')
                : null,
            'birthdate_hijri' => $this->companyUser->birthdate_hijri,
            'nationality' => $this->companyUser->country?->name,
            'postal_code' => $this->companyUser->postal_code,
            'landline_number' => $this->companyUser->landline_number,
            'management' => $this->companyUser->userProfessionalData?->management?->name,
            'department' => $this->companyUser->userProfessionalData?->department?->name,
            'job_type' => $this->companyUser->userProfessionalData?->jobType?->name,
            'job_code' => $this->companyUser->userProfessionalData?->job_code,
            'attendance_constraints' => $this->presentAttendanceConstraints($this->companyUser),
            // 'attendance_constraint' => $this->companyUser->userProfessionalData?->attendanceConstraint?->constraint_name,
            'whatsapp' => $this->generateWhatsAppUrl(),
            // 'whatsapp' => $this->companyUser->whatsapp,
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
                // 'WhatsApp' => $this->companyUser->whatsapp,
                'WhatsApp' => $this->generateWhatsAppUrl(),
                'LinkedIn' => $this->companyUser->linkedin,
                'Facebook' => $this->companyUser->facebook,
                'Instagram' => $this->companyUser->instagram,
                'Telegram' => $this->companyUser->telegram,
                'Snapchat' => $this->companyUser->snapchat,
            ]))) ?: null,
            'family-info' => $this->companyUser->userRelatives->map(fn($r) => trim($r->name . ($r->relationship ? ' (' . $r->relationship . ')' : '')))->filter()->join(', ') ?: null,
            'about-me' => $this->companyUser->userAbout?->about_me,
            'cv' => $this->companyUser->getFirstMedia('upload_biography')?->getFullUrl(),
            'certificates' => $this->companyUser->professionalCertificates->pluck('accreditation_name')->filter()->join(', ') ?: null,
            'qualification' => $this->companyUser->qualifications->pluck('academicQualification.name')->filter()->join(', ') ?: null,
            'experience' => $this->companyUser->userExperiences->map(fn($e) => trim($e->job_name . ($e->company_name ? ' (' . $e->company_name . ')' : '')))->filter()->join(', ') ?: null,
            'courses' => $this->companyUser->userEducationalCourses->pluck('name')->filter()->join(', ') ?: null,
            'work-license' => $this->companyUser->work_permit ?: null,
            'privileges' => $this->companyUser->userPrivileges->pluck('typePrivilege.name')->filter()->join(', ') ?: null,
            'official-data' => ($cr = $this->companyUser->contractualRelationships->first())
                ? ($cr->employment_name ?? $cr->registration_number)
                : null,
            'job-offer' => $this->companyUser->jobOffer?->job_offer_number,
            'contract-work' => $this->companyUser->employmentContract?->contract_number,
            'contract_duration' => $this->companyUser->employmentContract?->contract_duration,
            'education' => $this->companyUser->qualifications->pluck('academicQualification.name')->filter()->join(', ') ?: null,
            'passport-info' => $this->companyUser->passport ?: null,
            'residence-info' => $this->companyUser->residence ?: null,
            'number_of_projects' => null,
            'end_date' => $this->calculateContractEndDate(),
            'broker' => null,

        ];
    }
}
