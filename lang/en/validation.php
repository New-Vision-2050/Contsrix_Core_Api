<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'The :attribute field must be :digits digits.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'The :attribute field must be a valid email address.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute field format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute field must match :other.',
    'size' => [
        'array' => 'The :attribute field must contain :size items.',
        'file' => 'The :attribute field must be :size kilobytes.',
        'numeric' => 'The :attribute field must be :size.',
        'string' => 'The :attribute field must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => 'The :attribute field must be a string.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'The :attribute field must be a valid URL.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'The :attribute field must be a valid UUID.',
    'invalid-password' => 'invalid password.',
    'invalid-otp' => 'invalid Otp.',
    'invalid-credential' => 'invalid credential.',
    'invalid-to-login-with-otp' => 'invalid to login with otp',
    "can-not-resend-before"=>'can not resend before :minute minutes',
    'company_user_name' => 'Username is not valid',
    'company_name' => 'Name is not valid',
    'classification_number_already_in_use' => 'Classification number already in use',
    'classification_ number_available' => 'Classification number available',
    'commercial_registration_number' => 'Commercial registration number',
    'commercial_registration_number_with_another'=> 'Commercial registration_number with another',
    'phone_number_already_exists' => 'Phone number already exists',
    'invalid_phone_number'=> 'Invalid phone number',
    'phone_number_verified_successfully' => 'Phone number verified successfully',
    'email_already_exists' => 'Email already exists.',
    'email_verified_successfully' => 'Email verified successfully',
    "phone"=>"phone format is invalid",
    'delete-not-successful' => 'Delete not successful',
    'delete-successful' => 'Delete successful',
    "create-not-successful"=>"Create not successful",
    "create-successful"=>"Create successful",
    "update-not-successful"=>"Update not successful",
    "update-successful"=>"Update successful",
    'user-name' => 'The name must consist of three Arabic words without any symbols.',
    'user-email-error' => 'The email is already exist in the system in name :name',
    'user-email-success' => 'The email is already exist in the system ',
    'user-phone-error' => 'The phone is already exist in the system in name :name',
    'user-phone-success' => 'The phone is already exist in the system ',
    "identity-or-passport-required"=>"At least one of the identity fields (identity , Passport) is required",
    "passport-or-residence-or-border_number-required"=>"At least one of the identity fields (Passport, Residence, Border Number) is required",
    "company-not-found"=>"company not found",
    "branch-not-found"=>"branch not found",
    "integrity-error"=>"integrity error",
    "login-way-not-found"=>"login way not found",
    "user-not-found"=>"user not found ",
    "identifier-not-found"=>"identifier not found ",
    "delete-not-successful-must-have-one"=>"Delete not successful must have one",
    "deactivate-not-successful-must-have-one"=>"deactivate not successful must have one at leaset active ",
    "you-must-set-your-answers"=>"You must set your answers",
    "all-questions-are-required"=>"All questions are required",
    "invalid-token"=>"invalid token",
    "can-not-resend-otp"=>"can not resend otp",
    "action-took-before"=>"action took before",
    "lookups-value-not-correct"=>"lookups value not correct",
    "logo-not-valid"=>"logo not valid",
    "pick-another-location"=>"pick another location",
    "you-must-change-location-or-update-country"=>"you must change location or update country",
    "user-already-exists"=>"user already exists",
    "phone-exists"=>"phone exists",
    "phone_email_consistency-error"=>"phone and email consistency error",
    "can-not-delete-has-children"=>"can not delete has children",













    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

    'company_user' => [
        'first_name_required' => 'First name is required.',
        'last_name_required' => 'Last name is required.',
        'company_id_required' => 'Company is required.',
        'company_id_exists' => 'Selected company does not exist.',
        'country_id_exists' => 'Selected country does not exist.',
        'time_zone_id_exists' => 'Selected time zone does not exist.',
        'language_id_exists' => 'Selected language does not exist.',
        'currency_id_exists' => 'Selected currency does not exist.',
        'phone_required' => 'Phone is required.',
        'email_required' => 'Email is required.',
        'email_invalid' => 'Email must be a valid email address.',
        'job_title_required' => 'Job title is required.',
        'job_title_exists' => 'Selected job title does not exist.',
        'border_number_unique' => 'Border number must be unique.',
        'residence_unique' => 'Residence must be unique.',
        'passport_unique' => 'Passport must be unique.',
        'identity_unique' => 'Identity must be unique.',
    ],
    'company_legal' => [
        'registration_type_required' => 'Registration type is required.',
        'registration_type_exists' => 'The selected registration type does not exist.',
        'registration_number_required' => 'Registration number is required.',
        'start_date_required' => 'Start date is required.',
        'start_date_invalid' => 'Start date must be a valid date.',
        'start_date_before_end' => 'Start date must be before or equal to end date.',
        'end_date_required' => 'End date is required.',
        'end_date_invalid' => 'End date must be a valid date.',
        'end_date_after_start' => 'End date must be after or equal to start date.',
        'file_required' => 'A file is required.',
        'file_mimes' => 'The file must be one of the following types: pdf, jpeg, jpg, png, doc, docx.',
    ],
    'company' => [
        'name_required' => 'Company name is required.',
        'name_arabic' => 'Company name must be in Arabic only.',
        'username_required' => 'Username is required.',
        'username_unique' => 'Username is already taken.',
        'username_regex' => 'Username must only contain letters, numbers, and underscores.',
        'country_required' => 'Country is required.',
        'country_exists' => 'The selected country does not exist.',
        'field_required' => 'Company fields are required.',
        'field_array' => 'Company fields must be an array.',
        'field_id_required' => 'Each company field is required.',
        'field_id_uuid' => 'Each company field ID must be a valid UUID.',
        'field_id_exists' => 'One or more selected company fields are invalid.',
        'manager_required' => 'General manager is required.',
        'manager_uuid' => 'General manager ID must be a valid UUID.',
        'manager_exists' => 'The selected general manager does not exist.',
    ],


    'user_relative' => [
        'name_required' => 'The name field is required.',
        'user_id_required' => 'The user ID is required.',
        'marital_status_required' => 'The marital status is required.',
        'relationship_required' => 'The relationship field is required.',
        'phone_required' => 'The phone number is required.',
    ],
    
    'validation_failed'   => 'Validation failed',
    'unauthenticated'     => 'Unauthenticated',
    'unauthorized'        => 'Unauthorized',
    'resource_not_found'  => 'Resource not found',
];
