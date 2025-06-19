<?php

namespace Modules\Attendance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'date' => $this->date,
            'clock_in_time' => $this->clock_in_time,
            'clock_out_time' => $this->clock_out_time,
            'status' => $this->status,
            'is_late' => $this->is_late,
            'late_minutes' => $this->late_minutes,
            'is_early_departure' => $this->is_early_departure,
            'early_departure_minutes' => $this->early_departure_minutes,
            'total_work_hours' => $this->total_work_hours,
            'overtime_hours' => $this->overtime_hours,
            'clock_in_location' => $this->clock_in_latitude && $this->clock_in_longitude ? [
                'latitude' => $this->clock_in_latitude,
                'longitude' => $this->clock_in_longitude,
            ] : null,
            'clock_out_location' => $this->clock_out_latitude && $this->clock_out_longitude ? [
                'latitude' => $this->clock_out_latitude,
                'longitude' => $this->clock_out_longitude,
            ] : null,
            'notes' => $this->notes,
            'breaks' => BreakRecordResource::collection($this->whenLoaded('breakRecords')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
