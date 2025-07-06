<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DataClasses\LocationTrackingPoint;

class TrackLocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     * These rules are based on your LocationTrackingPoint data class.
     */
    public function rules(): array
    {
        return [
            'tracking_points'          => ['required', 'array', 'min:1'],
            'tracking_points.*.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'tracking_points.*.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'tracking_points.*.timestamp'    => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'tracking_points.*.accuracy'     => ['required', 'numeric', 'min:0'],
            'tracking_points.*.device_id'    => ['sometimes', 'string', 'max:255'],
            'tracking_points.*.app_version'  => ['sometimes', 'string', 'max:50'],
            'tracking_points.*.battery_level'=> ['sometimes', 'integer', 'between:0,100'],
            'tracking_points.*.network_type' => ['sometimes', 'string', 'max:50'],
            'tracking_points.*.location_source' => ['sometimes', 'string', 'max:50'],
        ];
    }

    /**
     * Get the validated data as an array of LocationTrackingPoint objects.
     * This is a helper method to easily pass clean data to the service.
     *
     * @return LocationTrackingPoint[]
     */
    public function getTrackingPoints(): array
    {
        $points = [];
        foreach ($this->validated('tracking_points') as $pointData) {
            $points[] = LocationTrackingPoint::fromArray($pointData);
        }
        return $points;
    }
}
