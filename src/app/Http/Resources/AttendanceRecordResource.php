<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AttendanceService;
use App\Http\Resources\UserResource;
use App\Http\Resources\AttendanceBreakResource;
use App\Http\Resources\ApplicationRecordResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    private function calculateActualTotalTime(AttendanceService $attendanceService)
    {
        return $attendanceService->calculateActualWorkTime($this->resource);
    }

    private function calculateTotalBreakTime(AttendanceService $attendanceService)
    {
        return $attendanceService->calculateRestTime($this->resource);
    }

    public function toArray($request)
    {
        $attendanceService = app(AttendanceService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'date' => $this->attendance_date,
            'clock_in' => $this->attendance_time->format('H:i:s'),
            'clock_out' => $this->leave_time->format('H:i:s'),
            'comment' => $this->comment,
            'total_time' => $this->calculateActualTotalTime($attendanceService),
            'total_break_time' => $this->calculateTotalBreakTime($attendanceService),
            'breaks' => AttendanceBreakRecordResource::collection($this->whenLoaded('rests')),
            'application' => ApplicationRecordResource::collection($this->whenLoaded('proposals')),
        ];
    }
}
