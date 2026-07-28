<?php

declare(strict_types=1);

namespace Modules\Attendance\Contracts;

use Carbon\CarbonImmutable;
use Modules\User\Models\User;

interface TemporaryLocationProvider
{
    /**
     * Extra geofences valid for this user right now.
     *
     * @return list<array{
     *   id: string, name: string, latitude: float, longitude: float,
     *   radius: int, source: string, expires_at: ?string, reference_id: ?string
     * }>
     */
    public function temporaryLocationsFor(User $user, CarbonImmutable $at): array;

    /** True when this user is legitimately working elsewhere at $at (used to suppress auto-absence). */
    public function isEngagedElsewhere(User $user, CarbonImmutable $at): bool;
}
