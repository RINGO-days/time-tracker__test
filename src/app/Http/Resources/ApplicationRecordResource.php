<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ApplicationRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $proposedRest = [];
        $proposedRests = $this->proposed_rest;
        foreach($proposedRests as $rest){
            if(isset($rest['rest_start']) && isset($rest['rest_end'])){
                $proposedRest[] = [
                    'proposed_rest_start' => Carbon::parse($rest['rest_start'])->format('H:i'),
                    'proposed_rest_end' => Carbon::parse($rest['rest_end'])->format('H:i')
                ];
            }
        }

        return [
            $this->mergeWhen(!empty($this->proposed_attendance),[
                'proposed_attendance' => [
                    'proposed_clock_in' => $this->proposed_attendance['attendance_time'] ?? null,
                    'proposed_clock_out' => $this->proposed_attendance['leave_time'] ?? null
                ]
            ]),
            $this->mergeWhen(!empty($proposedRest),[
                'proposed_rest' => $proposedRest
            ])
        ];
    }
}
