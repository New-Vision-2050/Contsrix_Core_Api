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
        'name' => [
            'required' => 'The holiday name is required.',
            'string' => 'The holiday name must be a string.',
            'max' => 'The holiday name must not exceed 255 characters.',
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
    ],

    // LeavePolicy validation messages
    'leave_policy' => [
        'name' => [
            'required' => 'The leave policy name is required.',
            'string' => 'The leave policy name must be a string.',
            'max' => 'The leave policy name must not exceed 255 characters.',
        ],
        'total_days' => [
            'integer' => 'The total days must be an integer.',
            'min' => 'The total days must be at least 0.',
        ],
        'day_type' => [
            'string' => 'The day type must be a string.',
            'max' => 'The day type must not exceed 100 characters.',
        ],
        'is_rollover_allowed' => [
            'boolean' => 'The rollover allowed field must be true or false.',
        ],
        'max_days_per_request' => [
            'integer' => 'The maximum days per request must be an integer.',
            'min' => 'The maximum days per request must be at least 1.',
        ],
        'upgrade_condition' => [
            'string' => 'The upgrade condition must be a string.',
        ],
        'is_allow_half_day' => [
            'boolean' => 'The allow half day field must be true or false.',
        ],
    ],
];
