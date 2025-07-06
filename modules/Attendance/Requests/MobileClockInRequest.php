<?php

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileClockInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in_time' => 'nullable|date',
            'location' => 'nullable|array',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'location.address' => 'nullable|string',
            'location.accuracy' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
            'offline_mode' => 'nullable|boolean',
            'optimize_battery' => 'nullable|boolean',
            'low_data_mode' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'location.latitude.required_with' => 'Latitude is required when providing location data.',
            'location.latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'location.longitude.required_with' => 'Longitude is required when providing location data.',
            'location.longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }
}
