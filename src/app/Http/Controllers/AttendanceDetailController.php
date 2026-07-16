<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use App\Models\Proposal;
use App\Http\Requests\ProposalRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


class AttendanceDetailController extends Controller
{
    /**
     * 一般ユーザーまたは管理者が月次勤怠リストから出勤データある日付の詳細ボタンを押した時の詳細画面表示(一般ユーザーと管理者で共通のアクション)
     * 詳細画面では選択した日付に登録されているユーザーの名前、選択した日付、入力フィールドに出勤時間、退勤時間、休憩前後の時間、修正理由を記載する備考欄を表示
     * * 動的セグメントでURLから出勤のIDをfindする
     * - if文を用いて、見つけた勤怠データから勤怠修正申請テーブル(proposalsテーブル)に紐付いたデータがあり、かつ申請ステータスが１(申請中)の場合、承認待ちの旨のコメントがある画面を表示する
     * - 上記以外の場合は変更したい日付の勤怠データが表示されているフォーム画面を表示する
     *
     * @param int $id 勤怠ID
     *
     * @return View
     */
    public function detail($id) : View
    {
        $attendance = Attendance::with('user','rests')
                    ->findOrFail($id);

        $proposal = Proposal::with('user','attendance')
                    ->where('user_id',auth()->id())
                    ->where('attendance_id',$attendance->id)
                    ->first();
        if($proposal && $proposal->status === 1){
            return view('staff.detailConfirm',compact('proposal'));
        }

        $details = [
            'attendance_id' => $attendance->id,
            'name' => $attendance->user->name,
            'date' => $attendance->attendance_date,
            'attendance' => $attendance->attendance_time->format('H:i'),
            'leave' => $attendance->leave_time ? $attendance->leave_time->format('H:i') : '',
        ];
        $rests = $attendance->rests;

        return view('common.detail',compact('details','rests'));
    }

    public function newDetail(Request $request) : View
    {
        $details = [
            'attendance_id' => 'new_id',
            'user_id' => auth()->id(),
            'name' => auth()->user()->name,
            'date' => $request->query('date'),
        ];
        $rests = [];
        $proposalStatus = Proposal::where('user_id',auth()->id())
                            ->where('target_date',$request->date)
                            ->latest()
                            ->value('status');
        return view('common.detail',compact('details','rests','proposalStatus'));
    }

    public function propose(ProposalRequest $request,$id) : RedirectResponse | View
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
                    if (!empty($restData['rest_id']) && empty($restData['rest_start'])) {
                        Rest::where('id', $restData['rest_id'])->delete();
                        continue;
                    }
                    
                    if (empty($restData['rest_start'])) {
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
            $proposal->update([
                'status' => 2
            ]);

            return redirect("/admin/attendance/list");
        }

        return view('staff.detailConfirm',compact('proposal'));
    }

    public function newDetailPropose(ProposalRequest $request,$id) : RedirectResponse | View
    {
        $user = User::findOrFail($id);
        $proposal_attendance = [
            'attendance_time' => $request->attendance,
            'leave_time' => $request->leave,
        ];

        $proposal_rest = [];
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
            'user_id' => $user->id,
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
                    'attendance_id' => $attendance->id,
                    'status' => 2
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

    public function applyList(Request $request) : View
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

    public function detailConfirmShow($id) : View
    {
        $proposal = Proposal::with(['user','attendance'])->findOrFail($id);

        return view('staff.detailConfirm',compact('proposal'));
    }
}