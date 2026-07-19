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
use App\Services\AttendanceService;


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
     * (スタッフと管理者で共通のアクション)
     *
     * - 動的セグメントから出勤データを取得
     * - 出勤データに紐付いているユーザー情報をサービスクラスに渡す。
     * - サービスクラスからproposalsテーブルに保存した情報がリターンされる。
     *
     * 管理者は日次勤怠画面にリダイレクト、スタッフは修正申請済み画面を表示
     * 
     * @param ProposalRequest $request バリデーション済みの勤怠修正データ
     * @param AttendanceService $attendanceService リクエストデータをproposalsテーブルに保存するサービス
     * @param int $id 勤怠ID
     * 
     * @return RedirectResponse 管理者の場合
     * @return View スタッフの場合
     */
    public function propose(ProposalRequest $request,AttendanceService $attendanceService,$id) : RedirectResponse | View
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        $user = $attendance->user;

        $proposal = $attendanceService->createAttendanceDetailProposal($request,$user);

        if(auth()->user()->is_admin){
            return redirect("/admin/attendance/list");
        }

        return view('staff.detailConfirm',compact('proposal'));
    }

    /**
     * **追加機能**
     * 新規で勤怠を作成するためのアクション(スタッフ、管理者共通)
     * 
     * - 基本機能は上記のproposeと同じ
     * - 動的セグメントから直接ユーザーIDを取得し、サービスクラスに渡している
     * 
     * 管理者は日次勤怠画面にリダイレクト、スタッフは修正申請済み画面を表示
     * 
     * @param ProposalRequest $request バリデーション済みの勤怠修正データ
     * @param AttendanceService $attendanceService リクエストデータをproposalsテーブルに保存するサービス
     * @param int $id ユーザーID
     * 
     * @return RedirectResponse 管理者の場合
     * @return View スタッフの場合
     */
    public function newDetailPropose(ProposalRequest $request,AttendanceService $attendanceService,$id) : RedirectResponse | View
    {
        $user = User::findOrFail($id);

        $proposal = $attendanceService->createAttendanceDetailProposal($request,$user);

        if(auth()->user()->is_admin){
            return redirect('/admin/attendance/list');
        }
        return view('staff.detailConfirm',compact('proposal'));
    }

    /**
     * 承認待ちもしくは承認済みの勤怠のリスト画面(クエリパラメータでタブの切り替え)
     * 
     * - スタッフの場合、自分のIDのみを取得
     * - クエリパラメータtabがapprovedの時、proposalsテーブルのステータスが２(承認済み)のデータを取得
     * - それ以外は承認待ちのデータを取得
     * 
     * スタッフと管理者は別のヘッダーを使用しているため、Viewファイルは同じだが、管理者の場合ヘッダー用の変数をViewに渡している。
     * 
     * @param Request $request クエリパラメータで承認待ちと承認済みのタブの切り替え
     * 
     * @return View
     */
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

    /**
     * スタッフが勤怠詳細画面から修正リクエストを送った後に表示される画面
     * 修正できませんのコメントとともにユーザーが修正申請した入力データが表示される
     * 
     * - 動的セグメントから修正申請データのIDを取得
     * - それらをViewファイルに渡して、画面表示する
     * 
     * @param int $id 修正申請データのID
     * 
     * @return View
     */
    public function detailConfirmShow($id) : View
    {
        $proposal = Proposal::with(['user','attendance'])->findOrFail($id);

        return view('staff.detailConfirm',compact('proposal'));
    }
}