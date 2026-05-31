<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Proposal;
use App\Http\Requests\ProposalRequest;

class AttendanceDetailController extends Controller
{
    public function detail(Request $request)
    {
        $attendance = Attendance::where('user_id',auth()->id())
                    ->where('attendance_date',$request->query('date'))
                    ->first();
        if(!$attendance){
            return redirect("/list")->with('message','勤務記録がありませんでした。');
        }

        $rests = Rest::where('attendance_id',$attendance->id)
                    ->get();

        $details = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'date' => $attendance->attendance_date,
            'attendance' => $attendance->attendance_time->format('H:i'),
            'leave' => $attendance->leave_time->format('H:i'),
        ];
        return view('staff.detail',compact('details','rests'));
    }

    public function propose(ProposalRequest $request,$id)
    {
        $proposal_attendance = [
            'attendance_time' => $request->attendance,
            'leave_time' => $request->leave,
        ];
        $proposal_rests = $request->rest;
        if($proposal_rests){
            foreach($proposal_rests as $key => $rest){
                if(!empty($rest['rest_start'])){
                    $proposal_rest[] = [
                        'rest_id' => $rest['rest_id'] ?? null,
                        'rest_start' => $rest['rest_start'],
                        'rest_end' => $rest['rest_end'],
                    ];
                };
            };
        };

        $proposal = Proposal::create([
            'user_id' => auth()->id(),
            'attendance_id' => $id,
            'proposed_attendance' => $proposal_attendance,
            'proposed_rest' => $proposal_rest,
            'remarks' => $request->remarks,
        ]);

        return view('staff.detailConfirm',compact('proposal'));
    }

    public function applyList()
    {
        $proposals = Proposal::with(['user','attendance'])->get();

        return view('staff.applyList',compact('proposals'));
    }
}