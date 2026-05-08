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

    'branch_id_required' => 'The branch field is required.',
    'management_id_required' => 'The management field is required.',

    // User Access validation messages
    'user_access' => [
        'branch_id_required' => 'The branch field is required.',
        'branch_id_integer' => 'The branch field must be an integer.',
        'branch_id_exists' => 'The selected branch is invalid.',
        'user_ids_required' => 'The user IDs field is required.',
        'user_ids_array' => 'The user IDs field must be an array.',
        'user_ids_min' => 'At least one user must be selected.',
        'user_id_required' => 'Each user ID is required.',
        'user_id_string' => 'Each user ID must be a string.',
        'user_id_uuid' => 'Each user ID must be a valid UUID.',
        'user_id_exists' => 'One or more selected users are invalid.',
    ],
    'job_type_id_required' => 'The job type field is required.',
    'job_title_id_required' => 'The job title field is required.',
    'job_code_required' => 'The job code field is required.',

    'attendance-list' => 'Attendance List',
    'attendance-list*attendance-list' => 'Attendance List',
    'attendance-map' => 'Attendance Map',
    'attendance-list*attendance-map' => 'Attendance Map',
    'attendance-constraints' => 'Attendance Constraints',
    'attendance-constraints*attendance-constraints' => 'Attendance Constraints',


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
    'mimes' => 'The :attribute field must have one of the following extensions: :values.',
    'mimetypes' => 'The :attribute field must have one of the following extensions: :values.',
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

    "self_parent"=>"self parent is not allowed",
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
    "delete-not-allowed"=>"Delete not allowed",
    'delete-successful' => 'Delete successful',
    "create-not-successful"=>"Create not successful",
    "create-successful"=>"Create successful",
    "update-not-successful"=>"Update not successful",
    "update-successful"=>"Update successful",
    "central_company_cannot_update_packages"=>"Central companies cannot update their packages",
    "company_cannot_update_own_packages"=>"Companies cannot update their own packages",
    'user-name' => 'The name must consist of three Arabic words without any symbols.',
    'user-email-error' => 'The email is already exist in the system in name :name',
    'user-email-success' => 'The email is already exist in the system ',
    'user-phone-error' => 'The phone is already exist in the system in name :name',
    'user-phone-success' => 'The phone is already exist in the system ',
    "identity-or-passport-required"=>"At least one of the identity fields (identity , Passport) is required",
    "passport-or-residence-or-border_number-required"=>"At least one of the identity fields (Passport, Residence, Border Number) is required",
    "company-not-found"=>"company not found",
    "management-not-found"=>"management not found",
    "company-not-active"=>"company not active",
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
    "client-already-exist-in-thies-branches"=>'client already exists in this branches',
    "employee-already-exist"=>'employee already exists in this branches',
    "broker-already-exist-in-thies-branches"=>'broker already exists in this branches',


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

    'attributes' => [
        "job_type_id"=>"Job type",
        "manager_id"=>"manager",
        "name"=>"name",
        "name_ar"=>"name",
        "name_en"=>"name",
        "state_id"=>"state",
        "city_id"=>"city",
        "country_id"=>"country",
        "branch_id"=>"branch",
        "management_id"=>"managemant",


    ],

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
        'regestration_number_required' => 'The registration number is required for this type.',
        'start_date_less_than_8_days' => 'The start date must be at least 8 days before the end date.',
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
        'marital_status_id_required' => 'The marital status is required.',
        'relationship_required' => 'The relationship field is required.',
        'phone_required' => 'The phone number is required.',
    ],

    'identity' => [
        'passport_end_date_required_with' => 'The passport end date is required when passport start date is present.',
        'passport_end_date_date' => 'The passport end date must be a valid date.',
        'passport_end_date_after' => 'The passport end date must be after the start date.',

        'identity_end_date_required_with' => 'The identity end date is required when identity start date is present.',
        'identity_end_date_date' => 'The identity end date must be a valid date.',
        'identity_end_date_after' => 'The identity end date must be after the start date.',

        'border_number_end_date_required_with' => 'The border number end date is required when border number start date is present.',
        'border_number_end_date_date' => 'The border number end date must be a valid date.',
        'border_number_end_date_after' => 'The border number end date must be after the start date.',

        'entry_number_end_date_required_with' => 'The entry number end date is required when entry number start date is present.',
        'entry_number_end_date_date' => 'The entry number end date must be a valid date.',
        'entry_number_end_date_after' => 'The entry number end date must be after the start date.',

        'work_permit_end_date_required_with' => 'The work permit end date is required when work permit start date is present.',
        'work_permit_end_date_date' => 'The work permit end date must be a valid date.',
        'work_permit_end_date_after' => 'The work permit end date must be after the start date.',
    ],

    'validation_failed'   => 'Validation failed',
    'unauthenticated'     => 'Unauthenticated',
    'unauthorized'        => 'Unauthorized',
    'resource_not_found'  => 'Resource not found',

    // New validation messages for residence, passport and identity
    'residence_validation_error' => 'Residence number is already used.',
    'passport_validation_error' => 'Passport number is already used.',
    'identity_validation_error' => 'Identity number is already used.',
    'border_number_validation_error' => 'Border number is already used.',
    'user-residence-error' => 'Residence number is already used by another user.',
    'user-residence-success' => 'Residence number is valid.',
    'user-passport-error' => 'Passport number is already used by another user.',
    'user-passport-success' => 'Passport number is valid.',
    'user-identity-error' => 'Identity number is already used by another user.',
    'user-identity-success' => 'Identity number is valid.',
    'user-border-number-error' => 'Border number is already used by another user.',
    'user-border-number-success' => 'Border number is valid.',

    'user_id_required' => 'The user ID is required.',
    'job_offer_number_required' => 'The job offer number is required.',
    'date_send_required' => 'The sending date is required.',
    'date_accept_required' => 'The acceptance date is required.',

    'job_name_required' => 'The job title is required.',
    'training_from_required' => 'The start date is required.',
    'training_to_required' => 'The end date is required.',
    'training_to_after_from' => 'The end date must be after or equal to the start date.',
    'company_name_required' => 'The company name is required.',
    'about_required' => 'The description is required.',

    'country_id_required' => 'Country is required.',
    'university_id_required' => 'University is required.',
    'academic_qualification_id_required' => 'Academic qualification is required.',
    'academic_specialization_id_required' => 'Academic specialization is required.',
    'study_rate_required' => 'Study rate is required.',
    'study_rate_numeric' => 'Study rate must be a number.',
    'graduation_date_required' => 'Graduation date is required.',
    "access-denied"=>"access denied",

  'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'files' => [
            'required' => 'The files field is required.',
            'array' => 'The files must be an array.',
            '*.required' => 'Each file is required.',
            '*.file' => 'Each item must be a valid file.',
            '*.mimes' => 'Each file must be a type of: pdf, jpeg, jpg, png, doc, docx.',
        ],
        'document_type_id' => [
            'required' => 'The document type is required.',
            'exists' => 'The selected document type is invalid.',
        ],
        'description' => [
            'required' => 'The description is required.',
        ],
        'role' => [
            'cannot_deactivate' => 'Cannot deactivate this role because it is assigned to users.',
        ],
        'document_number' => [
            'required' => 'The document number is required.',
            'numeric' => 'The document number must be a number.',
        ],
        'start_date' => [
            'required' => 'The start date is required.',
            'date' => 'The start date must be a valid date.',
            'before_or_equal' => 'The start date must be before or equal to the end date.',
            'date_format' => 'The start date format must be YYYY-MM-DD.',
        ],
        'end_date' => [
            'required' => 'The end date is required.',
            'date' => 'The end date must be a valid date.',
            'after_or_equal' => 'The end date must be after or equal to the start date.',
            'date_format' => 'The end date format must be YYYY-MM-DD.',
        ],
        'notification_date' => [
            'required' => 'The notification date is required.',
            'date' => 'The notification date must be a valid date.',
            'after_or_equal' => 'The notification date must be after or equal to the start date.',
            'before' => 'The notification date must be before the end date.',
            'date_format' => 'The notification date format must be YYYY-MM-DD.',
        ],
    ],

    'address' => [
        'country_id' => [
            'required' => 'The country is required.',
            'exists' => 'The selected country is invalid.',
        ],
        'state_id' => [
            'required' => 'The state is required.',
            'exists' => 'The selected state is invalid.',
        ],
        'city_id' => [
            'required' => 'The city is required.',
            'exists' => 'The selected city is invalid.',
        ],
        'neighborhood_name' => [
            'required' => 'The neighborhood name is required.',
        ],
        'street_name' => [
            'required' => 'The street name is required.',
        ],
        'building_number' => [
            'required' => 'The building number is required.',
        ],
        'additional_phone' => [
            'required' => 'The additional phone number is required.',
        ],
        'postal_code' => [
            'required' => 'The postal code is required.',
        ],
    ],
    'notification_date_7_days' => 'The notification date must be at least 7 days before the end date.',

      'branch' => [
        'name_required' => 'Branch name is required.',
        'name_string' => 'Branch name must be a string.',

        'parent_id_required' => 'Parent ID is required.',
        'parent_id_exists' => 'The selected parent ID is invalid.',

        'manager_id_required' => 'Manager ID is required.',
        'manager_id_exists' => 'The selected manager ID is invalid.',

        'phone_required' => 'Phone number is required.',
        'phone_invalid' => 'The phone number is not valid.',

        'email_required' => 'Email is required.',
        'email_invalid' => 'The email format is not valid.',

        'latitude_required' => 'Latitude is required.',
        'latitude_numeric' => 'Latitude must be a number.',

        'longitude_required' => 'Longitude is required.',
        'longitude_numeric' => 'Longitude must be a number.',

        'country_required' => 'Country is required.',
        'country_exists' => 'The selected country is invalid.',

        'state_required' => 'State is required.',
        'state_exists' => 'The selected state is invalid.',

        'city_required' => 'City is required.',
        'city_exists' => 'The selected city is invalid.',
    ],
    'company_official' => [
        'name_required' => 'The company name (English) is required.',
        'email_required' => 'The email address is required.',
        'email_valid' => 'The email must be a valid email address.',
        'phone_required' => 'The phone number is required.',
        'branch_required' => 'The branch name is required.',
        'company_type_required' => 'The company type is required.',
        'company_type_exists' => 'The selected company type does not exist.',
    ],

    'professional_bodie_id_required' => 'The professional body ID is required.',
    'accreditation_name_required' => 'The accreditation name is required.',
    'accreditation_number_required' => 'The accreditation number is required.',
    'accreditation_degree_required' => 'The accreditation degree is required.',
    'date_obtain_required' => 'The date of obtaining the certificate is required.',
    'date_obtain_date' => 'The date of obtaining the certificate must be a valid date.',
    'date_end_required' => 'The certificate expiry date is required.',
    'date_end_date' => 'The certificate expiry date must be a valid date.',
    'graduation_date_date' => 'Graduation date must be a valid date.',

    // Company user deletion validation messages
    'admin_account_cannot_be_deleted' => 'The admin account cannot be deleted.',
    'cannot_delete_yourself' => 'You cannot delete your own account.',
    'cannot_delete_company_owner' => 'A company owner account cannot be deleted.',
    'cannot_delete_company_has_users' => 'Cannot delete company because it has :count employee(s).',
    'cannot_delete_company_has_projects' => 'Cannot delete company because it has :count project(s).',
    'cannot_delete_company_has_branches' => 'Cannot delete company because it has :count branch(es).',
    'cannot_delete_company_has_managements' => 'Cannot delete company because it has :count management(s).',
    'cannot_delete_company_has_related_data' => 'Cannot delete company because it has related data (employees, projects, branches, managements). Please delete the related data first.',
    'cannot_delete_project_has_employees' => 'Cannot delete project because it has :count employee(s).',
    'cannot_delete_project_has_roles' => 'Cannot delete project because it has :count role(s).',
    'cannot_delete_project_has_related_data' => 'Cannot delete project because it has related data (employees, roles). Please delete the related data first.',
    'project-not-found' => 'Project not found.',
    'regular' => 'regular',
    'male' => 'male',
    'female' => 'female',
    'day_status' => [
        'work_day'=> 'work day',
        'holiday'=>'holiday',
        'day_off_or_weekend'=> 'day off or weekend',
        'in_location'=>'in loction',
        'clocked_out'=> 'clocked out'
    ],

    'unique_translation' => 'The :locale :attribute has already been taken.',
    'arabic' => 'Arabic',
    'english' => 'English',
];
