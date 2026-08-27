<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Services\PublicHolidayCalendarService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use PHPUnit\Framework\TestCase;

/**
 * A public holiday applies to an employee because of where they work, so the country comes
 * from their branch. These tests pin the fallback order, because every holiday-aware surface
 * — the calendar, history, report labels, the report's month-holiday counters and the
 * clock-in gate — reads it through this one method and must agree (INV-21).
 */
class PublicHolidayCountryResolutionTest extends TestCase
{
    private PublicHolidayCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PublicHolidayCalendarService();
    }

    public function test_the_branch_country_wins(): void
    {
        $user = $this->user(branchCountryId: 'branch-country', companyCountryId: 'company-country');

        $this->assertSame('branch-country', $this->service->countryIdForUser($user, 'contract-country'));
    }

    /**
     * A branch with no address on file must not silently cancel every holiday, so the
     * company's own country stands in.
     */
    public function test_the_company_country_stands_in_when_the_branch_has_no_address(): void
    {
        $user = $this->user(branchCountryId: null, companyCountryId: 'company-country');

        $this->assertSame('company-country', $this->service->countryIdForUser($user, 'contract-country'));
    }

    /**
     * The reporting services pass `employment_contracts.country_id` as the fallback, so
     * moving them onto the branch country cannot zero out a figure that used to count.
     */
    public function test_the_contract_country_is_the_last_resort(): void
    {
        $user = $this->user(branchCountryId: null, companyCountryId: null);

        $this->assertSame('contract-country', $this->service->countryIdForUser($user, 'contract-country'));
    }

    public function test_nothing_resolves_to_no_country(): void
    {
        $user = $this->user(branchCountryId: null, companyCountryId: null);

        $this->assertNull($this->service->countryIdForUser($user));
        $this->assertNull($this->service->countryIdForUser($user, ''));
    }

    public function test_a_missing_user_falls_back_to_the_given_country(): void
    {
        $this->assertSame('contract-country', $this->service->countryIdForUser(null, 'contract-country'));
        $this->assertNull($this->service->countryIdForUser(null));
    }

    public function test_no_country_means_no_holidays_rather_than_every_holiday(): void
    {
        $this->assertTrue($this->service->forCountry(null, '2026-08-01', '2026-08-31')->isEmpty());
        $this->assertTrue($this->service->forCountry('', '2026-08-01', '2026-08-31')->isEmpty());
    }

    /**
     * Relations are set directly rather than saved: the date casts format through the
     * connection on write, which a plain PHPUnit TestCase has no container for.
     */
    private function user(?string $branchCountryId, ?string $companyCountryId): User
    {
        $branch = new ManagementHierarchy();
        $branch->setRelation(
            'address',
            $branchCountryId === null
                ? null
                : (new CompanyAddress())->setRawAttributes(['country_id' => $branchCountryId])
        );

        $professionalData = new UserProfessionalData();
        $professionalData->setRelation('branch', $branch);

        $user = new User();
        $user->setRelation('userProfessionalData', $professionalData);
        $user->setRelation('branch', null);
        $user->setRelation(
            'company',
            $companyCountryId === null
                ? null
                : (new Company())->setRawAttributes(['country_id' => $companyCountryId])
        );

        return $user;
    }
}
