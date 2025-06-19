<?php

namespace Modules\Attendance\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
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
                ];
            }),
            'year' => $this->year,
            'allocated_days' => $this->allocated_days,
            'carryover_days' => $this->carryover_days,
            'used_days' => $this->used_days,
            'pending_days' => $this->pending_days,
            'remaining_days' => $this->remaining_days,
            'expired_days' => $this->expired_days,
            'expiry_date' => $this->expiry_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
