<?php

namespace Modules\Attendance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => $this->whenLoaded('leaveType', function () {
                return [
                    'id' => $this->leaveType->id,
                    'name' => $this->leaveType->name,
                    'code' => $this->leaveType->code,
                    'is_paid' => $this->leaveType->is_paid,
                ];
            }),
            'supervisor_id' => $this->supervisor_id,
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->name,
                    'email' => $this->supervisor->email,
                ];
            }),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'leave_duration_type' => $this->leave_duration_type,
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'attachment_path' => $this->attachment_path,
            'status' => $this->status,
            'supervisor_approved' => $this->supervisor_approved,
            'supervisor_approved_at' => $this->supervisor_approved_at,
            'supervisor_approved_by' => $this->supervisor_approved_by,
            'supervisor_comments' => $this->supervisor_comments,
            'hr_approved' => $this->hr_approved,
            'hr_approved_at' => $this->hr_approved_at,
            'hr_approved_by' => $this->hr_approved_by,
            'hr_comments' => $this->hr_comments,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
