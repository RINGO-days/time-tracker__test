<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Proposal;
use App\Http\Requests\ProposalRequest;

class AttendanceDetailController extends Controller
{
    public function detail(Request $request,$id)
    {
        $attendance = Attendance::with('user','rests')
                    ->find($id);

        $proposal = Proposal::with('user','attendance')
                    ->where('user_id',auth()->id())
                    ->where('attendance_id',$attendance->id)
                    ->first();
        if($proposal && $proposal->status === 1){
            return view('staff.detailConfirm',compact('proposal'));
        }

        $details = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'date' => $attendance->attendance_date,
            'attendance' => $attendance->attendance_time->format('H:i'),
            'leave' => $attendance->leave_time ? $attendance->leave_time->format('H:i') : '',
        ];
        $rests = Rest::where('attendance_id',$attendance->id)
                    ->get();
        return view('common.detail',compact('details','rests'));
    }

    public function newDetail(Request $request)
    {
        $details = [
            'id' => 'new_id',
            'name' => auth()->user()->name,
            'date' => $request->query('date'),
        ];
        $rests = [];
        return view('common.detail',compact('details','rests'));
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
            'target_date' => $request->date,
            'proposed_attendance' => $proposal_attendance,
            'proposed_rest' => $proposal_rest ?? null,
            'remarks' => $request->remarks,
        ]);

        if(auth()->user()->is_admin){
            $attendance = Attendance::find($id);

            $attendance->update([
                'attendance_time' => $proposal_attendance['attendance_time'],
                'leave_time' => $proposal_attendance['leave_time'],
            ]);

            if($proposal_rests){
                foreach($proposal_rests as $restData){
                    if(!empty($restData['rest_id'])){
                        Rest::where('id',$restData['rest_id'])
                            ->update([
                                'rest_start' => $restData['rest_start'],
                                'rest_end' => $restData['rest_end'],
                            ]);
                    }else{
                        Rest::create([
                            'attendance_id' => $attendance->id,
                            'rest_start' => $restData['rest_start'],
                            'rest_end' => $restData['rest_end'],
                        ]);
                    }
                }
            }
            return redirect('/admin/attendance/staff/{$id}');
        }

        return view('staff.detailConfirm',compact('proposal'));
    }

    public function newDetailPropose(ProposalRequest $request)
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
            'target_date' => $request->date,
            'proposed_attendance' => $proposal_attendance ?? null,
            'proposed_rest' => $proposal_rest ?? null,
            'remarks' => $request->remarks,
        ]);

        if(auth()->user()->is_admin){
            $attendance = Attendance::where('user_id',$proposal->user_id)
                        ->where('attendance_date',$proposal->target_date)
                        ->first();

            if($attendance){
                $attendance->update([
                    'attendance_time' => $proposal_attendance['attendance_time'],
                    'leave_time' => $proposal_attendance['leave_time'],
                ]);
            }else{
                $attendance = Attendance::create([
                    'user_id' => $proposal->user_id,
                    'attendance_date' => $proposal->target_date,
                    'attendance_time' => $proposal_attendance['attendance_time'],
                    'leave_time' => $proposal_attendance['leave_time'],
                ]);
                $proposal->update([
                    'attendance_id' => $attendance->id
                ]);
            }

            if($proposal_rests){
                foreach($proposal_rests as $restData){
                    if(empty($restData['rest_start'])){
                        continue;
                    }

                    if(!empty($restData['rest_id'])){
                        Rest::where('id',$restData['rest_id'])
                            ->update([
                                'rest_start' => $restData['rest_start'],
                                'rest_end' => $restData['rest_end'],
                            ]);
                    }else{
                        Rest::create([
                            'attendance_id' => $attendance->id,
                            'rest_start' => $restData['rest_start'],
                            'rest_end' => $restData['rest_end'],
                        ]);
                    }
                }
            }
            return redirect('/admin/attendance/list');
        }

        return view('staff.detailConfirm',compact('proposal'));
    }

    public function applyList(Request $request)
    {
        $query = Proposal::with('user','attendance');

        if(!auth()->user()->is_admin){
            $query->where('user_id',auth()->id());
        }

        if($request->query('tab') == 'approved'){
            $query->where('status',2);
        }else{
            $query->where('status',1);
        }

        $proposals = $query->get();

        if(auth()->user()->is_admin){
            return view('staff.applyList',compact('proposals'),['nav' => 'admin']);
        }
        
        return view('staff.applyList',compact('proposals'));
    }

    public function detailConfirmShow($id)
    {
        $proposal = Proposal::with(['user','attendance'])->findOrFail($id);

        return view('staff.detailConfirm',compact('proposal'));
    }
}