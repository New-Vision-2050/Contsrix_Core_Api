<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\DataClasses\LocationTrackingPoint;

class TrackLocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     * Updated to support array of tracking data with both track and geofence types.
     */
    public function rules(): array
    {
        return [
            '*' => ['required', 'array'],
            '*.type' => ['required', 'string', 'in:track,geofence'],
            '*.lat' => ['required', 'numeric', 'between:-90,90'],
            '*.lng' => ['required', 'numeric', 'between:-180,180'],
            '*.timestamp' => ['required', 'string'],
            '*.is_mock' => ['required', 'boolean'],
            
            '*.gps_status' => ['required_if:*.type,track', 'string'],
            
            '*.action' => ['required_if:*.type,geofence', 'string'],
            '*.id' => ['required_if:*.type,geofence', 'string'],
        ];
    }

    /**
     * Get all tracking data from the array
     *
     * @return array
     */
    public function getTrackingData(): array
    {
        return $this->validated();
    }

    /**
     * Convert tracking data to LocationTrackingPoint objects
     *
     * @return array
     */
    public function getTrackingPoints(): array
    {
        $trackingData = $this->getTrackingData();
        $points = [];
        
        foreach ($trackingData as $data) {
            $pointData = [
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'timestamp' => $data['timestamp'],
                'accuracy' => 5.0,
                'device_id' => 'mobile-app',
                'app_version' => '1.0.0',
                'battery_level' => 100,
                'network_type' => '4G',
                'location_source' => $data['type'] === 'track' ? 'GPS' : 'Network',
            ];
            
            $points[] = [
                'point' => LocationTrackingPoint::fromArray($pointData),
                'type' => $data['type'],
                'is_mock' => $data['is_mock'],
                'gps_status' => $data['gps_status'] ?? null,
                'action' => $data['action'] ?? null,
                'geofence_id' => $data['id'] ?? null,
            ];
        }
        
        return $points;
    }
}
