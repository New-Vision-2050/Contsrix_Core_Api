<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Domain;

use Modules\Attendance\Domain\Time\TimezoneResolver;
use Modules\Attendance\Models\Attendance;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Tests\TestCase;

/**
 * Contract test for TimezoneResolver.
 * Uses forceFill() + setRelation() so no database is touched.
 */
final class TimezoneResolverTest extends TestCase
{
    private TimezoneResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TimezoneResolver();
    }

    // -------------------------------------------------------------------------
    // forAttendance()
    // -------------------------------------------------------------------------

    public function test_for_attendance_returns_timezone_from_row(): void
    {
        $attendance = new Attendance();
        $attendance->forceFill(['timezone' => 'Asia/Tokyo']);

        $this->assertSame('Asia/Tokyo', $this->resolver->forAttendance($attendance));
    }

    public function test_for_attendance_falls_back_to_user_when_no_row_timezone(): void
    {
        $user = $this->makeUserWithZoneName('Asia/Riyadh');

        $attendance = new Attendance();
        $attendance->forceFill(['timezone' => null]);
        $attendance->setRelation('user', $user);

        $this->assertSame('Asia/Riyadh', $this->resolver->forAttendance($attendance));
    }

    // -------------------------------------------------------------------------
    // forUser()
    // -------------------------------------------------------------------------

    public function test_for_user_returns_zone_name_from_chain(): void
    {
        $user = $this->makeUserWithZoneName('Asia/Dubai');

        $this->assertSame('Asia/Dubai', $this->resolver->forUser($user));
    }

    public function test_for_user_returns_config_fallback_when_user_is_null(): void
    {
        $result = $this->resolver->forUser(null);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_for_user_returns_config_fallback_when_professional_data_is_null(): void
    {
        $user = new User();
        $user->setRelation('userProfessionalData', null);

        $result = $this->resolver->forUser($user);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_for_user_returns_config_fallback_when_branch_is_null(): void
    {
        $professionalData = new UserProfessionalData();
        $professionalData->setRelation('branch', null);

        $user = new User();
        $user->setRelation('userProfessionalData', $professionalData);

        $result = $this->resolver->forUser($user);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_for_user_returns_config_fallback_when_timezones_are_null(): void
    {
        $user = $this->makeUserWithTimezoneArray(null);

        $result = $this->resolver->forUser($user);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_for_user_returns_config_fallback_when_zone_name_key_missing(): void
    {
        // Valid array structure but missing the 'zoneName' key.
        $user   = $this->makeUserWithTimezoneArray([['gmtOffset' => 10800]]);
        $result = $this->resolver->forUser($user);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function test_for_user_returns_config_fallback_when_timezones_is_empty_array(): void
    {
        $user   = $this->makeUserWithTimezoneArray([]);
        $result = $this->resolver->forUser($user);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a User whose branch-TZ chain resolves to $zoneName. */
    private function makeUserWithZoneName(string $zoneName): User
    {
        return $this->makeUserWithTimezoneArray([['zoneName' => $zoneName]]);
    }

    /**
     * Build a User where the full chain is present but the leaf Country::timezones
     * is set to $timezones.  Uses Attendance as a neutral Eloquent proxy for the
     * address node (setRelation() stores any value; the type doesn't matter here).
     */
    private function makeUserWithTimezoneArray(mixed $timezones): User
    {
        // Leaf: country with timezones attribute.
        // Stored as JSON string so Eloquent's 'array' cast decodes it on access.
        $country = new \stdClass();
        $country->timezones = $timezones;

        // Generic Eloquent model as address proxy — only 'country' relation matters.
        $addressProxy = new Attendance();
        $addressProxy->setRelation('country', $country);

        $branch = new ManagementHierarchy();
        $branch->setRelation('address', $addressProxy);

        $professionalData = new UserProfessionalData();
        $professionalData->setRelation('branch', $branch);

        $user = new User();
        $user->setRelation('userProfessionalData', $professionalData);

        return $user;
    }
}
