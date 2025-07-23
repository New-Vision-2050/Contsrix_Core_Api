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
            'created_by'        => $branch->manager_id?? auth()->user()->id,
            'branch_ids'        => [(string) $branch->id], // Link to this branch
            'branch_locations'  => [
                [
                    'branch_id' => (string) $branch->id,
                    'name'      => $branch->name,
                    'address'   => $branch->address?->full_address,
                    'latitude'  => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'radius'    => 200 // Default radius of 200 meters
                ]
            ],
            'constraint_config' => [
                "radius_enforcement"=>[
                    "out_of_radius_time_threshold"=>30,
                    "unit"=>"minute"//hour, minute, day
                ],

                'time_rules' => [
                    'subtype' => 'multiple_periods',
                    'weekly_schedule' => [
                        'sunday'    => ['enabled' => true, "total_work_hours"=>9, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'monday'    => ['enabled' => true, "total_work_hours"=>9, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'tuesday'   => ['enabled' => true, "total_work_hours"=>9, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'wednesday' => ['enabled' => true, "total_work_hours"=>9, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'thursday'  => ['enabled' => true, "total_work_hours"=>9, 'periods' => [['start_time' => '08:30', 'end_time' => '17:30']]],
                        'friday'    => ['enabled' => false, "total_work_hours"=>9,'periods' => []],
                        'saturday'  => ['enabled' => false, "total_work_hours"=>9,'periods' => []]
                    ],
                    'lateness_rules' => [
                        'prevent_lateness' => true,
                        'grace_period_minutes' => 15,
                        "unit"=>"minute"//hour, minute, day

                    ],
                    'early_departure_rules' => [
                        'prevent_early_departure' => true,
                        'grace_period_minutes' => 10,
                        "unit"=>"minute"//hour, minute, day
                    ],
                    'overtime_rules' => [
                        'requires_approval' => true,
                        'approval_threshold_minutes'=> 30,
                        "unit"=>"minute"//hour, minute, day
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
