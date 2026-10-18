<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Leave Module Validation Messages (English)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for validation messages
    | in the Leave module components
    |
    */

    // PublicHoliday validation messages
    'public_holiday' => [
        'invalid_year' => 'These dates do not exist in the holiday year.',
        'month_day' => 'The date must be a valid month and day in MM-DD format.',
        'name' => [
            'required' => 'The holiday name is required.',
            'string' => 'The holiday name must be a string.',
            'max' => 'The holiday name must not exceed 255 characters.',
        ],
        'branch_id' => [
            'required' => 'The branch ID is required.',
            'integer' => 'The branch ID must be an integer.',
            'exists' => 'The selected branch does not exist.',
        ],
        'country_id' => [
            'required' => 'The country ID is required.',
            'string' => 'The country ID must be a string.',
            'uuid' => 'The country ID must be a valid UUID.',
            'exists' => 'The selected country does not exist.',
        ],
        'date_start' => [
            'required' => 'The holiday start date is required.',
            'date' => 'The holiday start date must be a valid date.',
            'date_format' => 'The holiday start date must be in Y-m-d format.',
        ],
        'date_end' => [
            'required' => 'The holiday end date is required.',
            'date' => 'The holiday end date must be a valid date.',
            'date_format' => 'The holiday end date must be in Y-m-d format.',
            'after_or_equal' => 'The holiday end date must be after or equal to the start date.',
        ],
    ],

    // LeaveType validation messages
    'leave_type' => [
        'name' => [
            'required' => 'The leave type name is required.',
            'string' => 'The leave type name must be a string.',
            'max' => 'The leave type name must not exceed 255 characters.',
        ],
        'is_payed' => [
            'boolean' => 'The paid leave field must be true or false.',
        ],
        'is_deduct_from_balance' => [
            'boolean' => 'The deduct from balance field must be true or false.',
        ],
        'requires_attachment' => [
            'boolean' => 'The requires attachment field must be true or false.',
        ],
    ],

    // LeavePolicyCategory validation messages
    'leave_policy_category' => [
        'name' => [
            'required' => 'The category name is required.',
            'string' => 'The category name must be a string.',
            'max' => 'The category name must not exceed 255 characters.',
            'unique' => 'The category name has already been taken.',
        ],
    ],

    // LeavePolicy validation messages
    'leave_policy' => [
        'leave_category_id' => [
            'required' => 'The leave policy category is required.',
            'uuid' => 'The leave policy category must be a valid UUID.',
            'exists' => 'The selected leave policy category does not exist.',
        ],
        'name' => [
            'required' => 'The leave policy name is required.',
            'string' => 'The leave policy name must be a string.',
            'max' => 'The leave policy name must not exceed 255 characters.',
        ],
        'leave_type_id' => [
            'required' => 'The leave type is required.',
            'uuid' => 'The leave type must be a valid UUID.',
            'exists' => 'The selected leave type does not exist.',
        ],
        'max_balance_days' => [
            'integer' => 'The maximum balance days must be an integer.',
            'min' => 'The maximum balance days must be at least 0.',
        ],
        'total_days' => [
            'required' => 'The number of leave days is required for balance-based policies.',
            'integer' => 'The total days must be an integer.',
            'min' => 'The total days must be at least 0.',
        ],
        'min_notice_days' => [
            'integer' => 'The minimum notice days must be an integer.',
            'min' => 'The minimum notice days must be at least 0.',
        ],
        'day_calculation_type' => [
            'required' => 'The day calculation type is required for balance-based policies.',
            'in' => 'The day calculation type must be one of: work_day, calendar.',
        ],
        'entitlement_type' => [
            'required' => 'The entitlement type is required for balance-based policies.',
            'in' => 'The entitlement type must be one of: upfront, monthly.',
        ],
        'entitlement_start_from' => [
            'in' => 'The entitlement start from must be one of: hire_date.',
        ],
        'promotion_type' => [
            'in' => 'The promotion type must be one of: after_duration.',
        ],
        'promotion_duration_months' => [
            'integer' => 'The promotion duration must be an integer.',
            'required_if' => 'The promotion duration is required when promotion type is after duration.',
        ],
        'is_rollover_allowed' => [
            'boolean' => 'The rollover allowed field must be true or false.',
        ],
        'rollover_duration_months' => [
            'integer' => 'The rollover duration must be an integer.',
        ],
        'allow_exceed_balance' => [
            'boolean' => 'The allow exceed balance field must be true or false.',
        ],
        'max_days' => [
            'required' => 'The maximum leave days is required for non-balance policies.',
            'integer' => 'The maximum days must be an integer.',
            'min' => 'The maximum days must be at least 0.',
        ],
    ],

    // EmploymentContract leave category messages
    'employment_contract' => [
        'leave_category_id' => [
            'invalid' => 'The leave policy category is invalid.',
        ],
    ],

    // Export validation messages
    'export' => [
        'format' => [
            'invalid' => 'The export format must be xlsx or csv.',
        ],
        'name' => [
            'string' => 'The name filter must be a string.',
            'max' => 'The name filter must not exceed 255 characters.',
        ],
        'total_days' => [
            'integer' => 'The total days filter must be an integer.',
            'min' => 'The total days filter must be at least 0.',
        ],
        'day_calculation_type' => [
            'in' => 'The day calculation type must be one of: work_day, calendar.',
        ],
        'leave_type_id' => [
            'uuid' => 'The leave type filter must be a valid UUID.',
            'exists' => 'The selected leave type does not exist.',
        ],
        'ids' => [
            'array' => 'The ids filter must be an array.',
            'uuid' => 'Each id must be a valid UUID.',
            'exists' => 'One or more selected items do not exist.',
        ],
    ],
];
