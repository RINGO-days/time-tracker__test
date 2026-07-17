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
     * 出勤データの詳細画面、または承認待ち画面を表示する。
     *
     * 詳細画面では選択した日付に登録されているユーザーの名前、選択した日付、入力フィールドに出勤時間、退勤時間、休憩前後の時間、修正理由を記載する備考欄を表示
     * 【処理の流れ】
     * 1 動的セグメントでURLから出勤のIDをfindする
     * 2 見つけた勤怠データから勤怠修正申請テーブル(proposalsテーブル)に紐付いたデータがあり、かつ申請ステータスが１(申請中)の場合、承認待ちの旨のコメントがある画面を表示する
     * 3 上記以外の場合は変更したい日付の勤怠データが表示されているフォーム画面を表示する
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

    /**
     * **追加機能**
     * スタッフ画面の月次勤怠リストに記載されていないレコードの詳細ボタンを押した時の画面表示
     *
     * - 選択した日付をクエリパラメータから取得し、詳細画面に表示する
     * - 休憩データを入力できる空欄を作るため、$restsは空の配列を渡す
     * - 選択した日付の勤怠から承認状態のステータスを取得し、承認待ちだった場合、申請済みのメッセージが表示される
     *
     * @param Request $request クエリパラメータから新規勤怠登録する日付を取得
     *
     * @return View
     */
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

    /**
     * 勤怠を修正申請する詳細画面から修正ボタンを押したときのアクション
     *
     * - 動的セグメントから出勤データを取得
     * - 送られてきた出勤時間と退勤時間はjson形式で保存
     * - 同じく送られてきた休憩データは複数個ある可能性があるため、foreachで１件ずつ、配列形式で保存する
     * (後に休憩データを削除するロジックがあるためrest_idも同時に保存する)
     * - 送られてきたデータ全てをproposalsテーブルに保存する
     *
     * 【管理者用のロジック】
     * - 先ほどproposalsテーブルに保存したデータをattendancesテーブル、restsテーブルにアップデートする
     * **追加機能**
     * - すでに休憩データのデフォルト値があった状態で、休憩開始時間と休憩終了時間を空欄にして送られてきた場合、送られてきたrest_idに該当する休憩データを削除する
     */
    public function propose(ProposalRequest $request,$id) : RedirectResponse | View
    {
        $attendance = Attendance::findOrFail($id);

        $proposal_attendance = [
            'attendance_time' => $request->attendance,
            'leave_time' => $request->leave,
        ];
        $proposal_rests = $request->rest;
        if($proposal_rests){
            foreach($proposal_rests as $key => $rest){
                if(!empty($rest['rest_id']) || !empty($rest['rest_start'])){
                    $proposal_rest[] = [
                        'rest_id' => $rest['rest_id'] ?? null,
                        'rest_start' => $rest['rest_start'] ?? null,
                        'rest_end' => $rest['rest_end'] ?? null
                    ];
                }
            }
        }

        $proposal = Proposal::create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'target_date' => $request->date,
            'proposed_attendance' => $proposal_attendance,
            'proposed_rest' => $proposal_rest ?? null,
            'remarks' => $request->remarks,
        ]);

        if(auth()->user()->is_admin){
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

    /**
     * **追加機能**
     * 新規で勤怠を作成するためのアクション(スタッフ、管理者共通)
     * 
     * - クエリパラメータから新規登録するスタッフのIDを取得
     * - 
     */
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