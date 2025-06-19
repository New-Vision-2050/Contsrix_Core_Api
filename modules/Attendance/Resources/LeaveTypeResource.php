<?php

namespace Modules\Attendance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'default_days_per_year' => $this->default_days_per_year,
            'requires_approval' => $this->requires_approval,
            'requires_hr_approval' => $this->requires_hr_approval,
            'is_paid' => $this->is_paid,
            'allow_half_day' => $this->allow_half_day,
            'is_active' => $this->is_active,
            'min_days_notice_required' => $this->min_days_notice_required,
            'allow_carryover' => $this->allow_carryover,
            'max_carryover_days' => $this->max_carryover_days,
            'is_sick_leave' => $this->is_sick_leave,
            'requires_attachment' => $this->requires_attachment,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
