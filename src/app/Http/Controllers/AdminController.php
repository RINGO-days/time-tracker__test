<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use App\Models\Proposal;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AttendanceService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;


class AdminController extends Controller
{
    /**
     * 管理者用の日時勤怠画面
     * 1日ごとにスタッフの出勤のデータを表示
     *
     * - クエリパラメータから日付を取得。クエリパラメータがなかった場合、今日の日付が選ばれる
     * - 選択した日付から翌日と前日の変数($preDay,$nextDay)を作成
     * - 作成した変数をViewファイルでクエリパラメータとして入力することで1日ずつページ変更が可能
     *
     * - クエリパラメータの日付から取得した勤怠データをコレクションメソッドeachで１件ずつ処理をする
     * - サービスクラスの計算ロジックから合計の休憩時間と実労働時間を取得
     *
     * @param Request $request クエリパラメータから日付情報を取得
     * @param AttendanceService $attendanceService 休憩時間と実労働時間の計算ロジック
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function dailyAttendance(Request $request,AttendanceService $attendanceService) : View
    {
        $targetDay = Carbon::parse($request->get('day',Carbon::today()->toDateString()));
        $preDay = Carbon::parse($targetDay)->copy()->subDay()->format('Y/m/d');
        $nextDay = Carbon::parse($targetDay)->copy()->addDay()->format('Y/m/d');

        $dailyAttendances = Attendance::with(['user','rests'])
                            ->where('attendance_date',$targetDay)
                            ->get();

        $dailyAttendances->each(function($dailyAttendance) use ($attendanceService){
            $dailyAttendance->rest_total_str = $attendanceService->calculateRestTime($dailyAttendance);

            if($dailyAttendance->attendance_time && $dailyAttendance->leave_time){
                $dailyAttendance->actual_work_time_str = $attendanceService->calculateActualWorkTime($dailyAttendance);
            }
        });

        return view('admin.dailyAttendance',compact('targetDay','preDay','nextDay','dailyAttendances'),['nav' => 'admin']);
    }

    /**
     * 管理者用の勤怠リストから詳細ボタンを押した時の画面表示
     * スタッフ、管理者共通のViewファイル
     *
     * - 動的セグメントから勤怠IDを取得
     * - 取得した勤怠情報をViewファイルに渡す
     * - 取得した勤怠情報に紐付いている修正データのステータスを取得し、Viewファイルにてステータスが承認待ちだった場合、注意コメントを画面に表示
     *
     * @param int $id 勤怠ID
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function editDetail($id) : View
    {
        $attendance = Attendance::with('user','rests','proposals')
                    ->findOrFail($id);
        $rests = $attendance->rests;

        $details = [
            'attendance_id' => $attendance->id,
            'name' => $attendance->user->name,
            'date' => $attendance->attendance_date,
            'attendance' => $attendance->attendance_time->format('H:i'),
            'leave' => $attendance->leave_time ? $attendance->leave_time->format('H:i') : '',
        ];
        $proposalStatus = Proposal::where('attendance_id',$attendance->id)->latest()->value('status');

        return view('common.detail',compact('details','rests','proposalStatus'),['nav' => 'admin']);
    }

    /**
     * 管理者用のスタッフ一覧画面
     *
     * usersテーブルからスタッフ情報を全て取得
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function staffList() : View
    {
        $staffs = User::where('is_admin',false)->get();
        return view('admin.staffList',compact('staffs'),['nav' => 'admin']);
    }

    /**
     *  管理者用のスタッフ毎の月次勤怠画面
     *
     * - リクエストボディをサービスクラスgetMonthPeriodに渡し、月の指定をする変数を取得($preMonth,$nextMonth,$targetMonth)
     * - 同じくリクエストボディをサービスクラスgetMonthlyRecordsに渡し、労働日時(曜日)、労働時間、休憩時間などを取得
     * - スタッフ情報も画面に表示するため、動的セグメントからスタッフ情報を取得
     *
     * @param Request $request クエリパラメータから表示したい月を取得
     * @param AttendanceService $attendanceService ページ変更のための変数の取得と月毎の勤怠情報の取得
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function staffMonthlyAttendance(Request $request,AttendanceService $attendanceService,$id) : View
    {
        extract($attendanceService->getMonthPeriod($request));
        $records = $attendanceService->getMonthlyRecords($request);
        $user = User::findOrFail($id);

        return view('admin.staffMonthlyAttendance',compact('records','preMonth','nextMonth','targetMonth','user'),['nav' => 'admin']);
    }

    /**
     * 管理者用のスタッフの月次勤怠リストからCSVファイルとして表示されている月の勤怠のCSVファイルの出力
     *
     * - リクエストボディをサービスクラスgetMonthPeriodに渡し、月の指定をする変数を取得($preMonth,$nextMonth,$targetMonth)
     * - StreamedResponseを用いてデータを順次ブラウザへ出力する
     * - UTF-8のBOMを指定することでExcelなどで文字化けを防ぐ
     * - リクエストボディをサービスクラスgetMonthlyRecordsに渡し、取得した１ヶ月の勤怠情報をCSVのデータとして取得する
     *
     * @param Request $request クエリパラメータからCSV出力したい月を取得
     * @param AttendanceService $attendanceService CSV出力のための変数の取得と月毎の勤怠情報の取得
     *
     * @return StreamedResponse CSVダウンロード用のストリームレスポンス
     */
    public function export(Request $request,AttendanceService $attendanceService) : StreamedResponse
    {
        extract($attendanceService->getMonthPeriod($request));
        $user = User::findOrFail($request->id);

        $fileName = $user->name . 'さんの勤怠＿' . $targetMonth . '.csv';

        $response = new StreamedResponse(function() use($startOfMonth,$endOfMonth,$attendanceService,$request){
            $stream = fopen('php://output','w');

            fwrite($stream,pack('C*', 0xEF, 0xBB, 0xBF));

            $header = ['日付','出勤','退勤','休憩','合計'];
            fputcsv($stream,$header);

            $records = $attendanceService->getMonthlyRecords($request);

            foreach($records as $row)
                fputcsv($stream,[
                    $row['date']."[".$row['week']."]",
                    $row['attendance'],
                    $row['leave'],
                    $row['rest'],
                    $row['actualTime'],
                ]);

            fclose($stream);
            });
        $response->headers->set('Content-Type','text/csv');
        $response->headers->set('Content-Disposition','attachment; filename="' . rawurlencode($fileName) . '"');
        return $response;
    }

    /**
     * 管理者用の修正申請リストから詳細ボタンを押した時の画面表示
     *
     * - 動的セグメントから、修正申請データのIDを取得
     *
     * @param $attendance_correct_request_id 修正申請データのID
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function requestShow($attendance_correct_request_id) : View
    {
        $proposal = Proposal::with(['user','attendance.rests'])->findOrFail($attendance_correct_request_id);

        return view('admin.correctionApprove',compact('proposal'),['nav' => 'admin']);
    }

    /**
     * 修正申請の詳細画面で、承認ボタンを押した時のアクション
     * DBトランザクションによりデータ不整合を防ぐ
     *
     * - 動的セグメントから、修正申請データのIDを取得
     * - 出勤時間と退勤時間はそのままattendancesテーブルにアップデート
     * **追加機能**
     * * 既存の休憩IDがあり、かつ時間が空になった場合は該当の休憩レコードを個別削除。
     *
     * - すでに登録されている休憩データIDがキーになっている修正申請はアップデート
     * - ないもキー名がないデータは新規休憩データとして作成
     * - 最後に修正申請データのステータスを２(承認済み)に変更
     *
     * @param $attendance_correct_request_id 修正申請データのID
     *
     * @return ResponseRedirect
     */
    public function approve($attendance_correct_request_id) : RedirectResponse
    {

        $proposal = Proposal::with('attendance.rests')
        ->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($proposal){
            $proposal->attendance->update([
                'attendance_time' => $proposal->proposed_attendance['attendance_time'],
                'leave_time' => $proposal->proposed_attendance['leave_time'],
            ]);

            $attendance = $proposal->attendance;
            if($proposal->proposed_rest === null || empty($proposal->proposed_rest)){
                $attendance->rests()->delete();
            }else{
                foreach($proposal->proposed_rest ?? [] as $proposalRest){
                    if (!empty($proposalRest['rest_id']) && empty($proposalRest['rest_start'])) {
                        Rest::where('id', $proposalRest['rest_id'])->delete();
                        continue;
                    }
                    if(!empty($proposalRest['rest_id'])){
                        Rest::where('id',$proposalRest['rest_id'])
                            ->update([
                                'rest_start' => $proposalRest['rest_start'],
                                'rest_end' => $proposalRest['rest_end'],
                            ]);
                    }else{
                        $attendance->rests()->create([
                            'rest_start' => $proposalRest['rest_start'],
                            'rest_end' => $proposalRest['rest_end'],
                        ]);
                    }

                }
            }
            $proposal->update([
                'status' => 2
            ]);
        });

        return back();
    }

    /**
     * **追加機能**
     * 管理者用の勤怠情報がないレコードの詳細ボタンを押した時の画面
     * 新規で勤怠を作成する
     *
     * - 動的セグメントからユーザー情報を取得
     * - クエリパラメータから勤怠を作成したい日付を取得
     * - 休憩データは空の配列の状態で渡す
     *
     * @param Request $request クエリパラメータから作成したい日付の取得
     * @param int $id ユーザーID
     *
     * @return View 管理者用のヘッダー表示のための変数をViewファイルに渡す
     */
    public function newDetailByAdmin(Request $request,$id) : View
    {
        $user = User::findOrFail($id);
        $details = [
            'attendance_id' => 'new_id',
            'user_id' => $user->id,
            'name' => $user->name,
            'date' => $request->query('date'),
        ];
        $rests = [];
        return view('common.detail',compact('details','rests'),['nav' => 'admin']);
    }

}
