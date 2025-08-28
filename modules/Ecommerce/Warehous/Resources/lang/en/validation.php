<?php

return [

  'name_required' => 'The warehouse name is required.',
        'name_string' => 'The warehouse name must be a string.',
        'name_max' => 'The warehouse name may not be greater than :max characters.',
        'is_default_boolean' => 'The "is default" field must be true or false.',
        'is_default_unique' => 'Only one warehouse can be set as default for this company.',
        'country_id_required' => 'The country ID is required.',
        'country_id_uuid' => 'The country ID must be a valid UUID.',
        'country_id_exists' => 'The selected country ID does not exist.',
        'city_id_required' => 'The city ID is required.',
        'city_id_uuid' => 'The city ID must be a valid UUID.',
        'city_id_exists' => 'The selected city ID does not exist.',
        'district_string' => 'The district must be a string.',
        'district_max' => 'The district may not be greater than :max characters.',
        'street_required' => 'The street is required.',
        'street_string' => 'The street must be a string.',
        'street_max' => 'The street may not be greater than :max characters.',
        'latitude_numeric' => 'The latitude must be a number.',
        'latitude_between' => 'The latitude must be between :min and :max.',
        'longitude_numeric' => 'The longitude must be a number.',
        'longitude_between' => 'The longitude must be between :min and :max.',
];
