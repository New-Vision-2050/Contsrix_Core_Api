<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\Uuid;

/**
 * Service dedicated to creating default constraints for new entities like branches.
 */
class DefaultConstraintService
{
    /**
     * Creates a default, fully configured attendance constraint for a new branch.
     *
     * @param ManagementHierarchy $branch The newly created branch model.
     * @return void
     */
    public function createForBranch(ManagementHierarchy $branch): void
    {
        // Don't create a constraint if a manager isn't assigned yet.
        if (!$branch->manager_id) {
            return;
        }

        // Check if a default constraint already exists to avoid duplicates.
        if ($branch->attendanceConstraints()->wherePivot('is_default', true)->exists()) {
            return;
        }

        // --- CORRECTED DATA ARRAY ---
        $constraintData = [
            'company_id'        => $branch->company_id,
            'constraint_name'   => $branch->name,
            'constraint_type'   => 'regular',
            'is_active'         => true,
            'priority'          => 1, // Default priority for branch-level constraints
            'created_by'        => $branch->manager_id,
            'branch_ids'        => [(string) $branch->id], // Link to this branch
            'branch_locations'  => [
                [
                    'branch_id' => (string) $branch->id,
                    'name'      => $branch->name,
                    'address'   => $branch->address?->full_address,
                    'latitude'  => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'radius'    => 300 // Default radius of 300 meters
                ]
            ],
            'constraint_config' => [
                'time_rules' => [
                    'subtype' => 'multiple_periods',
                    'weekly_schedule' => [
                        'sunday'    => ['enabled' => true, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'monday'    => ['enabled' => true, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'tuesday'   => ['enabled' => true, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'wednesday' => ['enabled' => true, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'thursday'  => ['enabled' => true, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'friday'    => ['enabled' => false, 'periods' => []],
                        'saturday'  => ['enabled' => false, 'periods' => []]
                    ],
                    'lateness_rules' => [
                        'prevent_lateness' => true,
                        'grace_period_minutes' => 15
                    ],
                    'early_departure_rules' => [
                        'prevent_early_departure' => true,
                        'grace_period_minutes' => 10
                    ],
                    'overtime_rules' => [
                        'requires_approval' => true,
                        'approval_threshold_minutes' => 30
                    ],
                ],
                'type_attendance' => [
                    'location' => true,
                    'fingerprint' => false
                ]
            ]
        ];

        // Create the constraint model instance.
        $constraint = AttendanceConstraint::create($constraintData);

        // Attach the new constraint to the branch and mark it as the default.
        $branch->attendanceConstraints()->attach($constraint->id, ['is_default' => true]);
    }
}
