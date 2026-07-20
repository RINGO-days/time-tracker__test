<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use App\Services\AttendanceService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;


class AttendanceController extends Controller
{
    /**
     * 一般スタッフ用の打刻画面を表示する
     * 本日の曜日、および打刻時のステータスによって表示画面のステータスアイコン（勤務外/出勤中/休憩中/退勤済）が変化
     *
     * @return View
     */
    public function index() : View
    {
        $weeks = ['日','月','火','水','木','金','土'];
        $todayWeek = $weeks[now()->dayOfWeek];

        $todayAttendance = Attendance::where('user_id',auth()->id())
            ->where('attendance_date',Carbon::today()->toDateString())
            ->latest()->first();

        if(!$todayAttendance){
            $condition = '勤務外';
        }else{
            switch($todayAttendance->status){
                case 1:
                    $condition = '出勤中';
                    break;
                case 2:
                    $condition = '休憩中';
                    break;
                case 3:
                    $condition = '退勤済';
                    break;
            }
        }
        return view('staff.attendance',compact('condition','todayWeek'));
    }

    /**
     * 打刻画面で出勤、もしくは退勤ボタンを押した時の処理
     * DBトランザクションによりデータ不整合を防ぐ
     * 処理後は打刻画面に戻る
     *
     * 【出勤】
     * 新しくattendancesテーブルに現在時刻などのレコードを作成、出勤ステータスはデフォルトは１(出勤中)
     * 【退勤】
     * 退勤ボタンを押すと退勤時の時間を保存し出勤ステータスを３(退勤済み)に更新する。
     *
     * @return RedirectResponse
     */
    public function attendance() : RedirectResponse
    {
        $attendance = Attendance::where('user_id',auth()->id())
            ->where('attendance_date',Carbon::today()->toDateString())
            ->first();

        DB::transaction(function () use ($attendance){
            if(!$attendance){
                Attendance::create([
                    'user_id' => auth()->id(),
                    'attendance_date' => Carbon::today()->toDateString(),
                    'attendance_time' => now(),
                ]);
            }else{
                $attendance->update([
                    'leave_time' => now(),
                    'status' => 3
                ]);
            }
        });
        return back();
    }

    /**
     * 打刻画面で休憩入、もしくは休憩戻ボタンを押した時の処理
     * DBトランザクションによりデータ不整合を防ぐ
     * 処理後は打刻画面に戻る
     *
     * 【休憩入】
     * 出勤ステータスが１(出勤中)の場合に、restsテーブルに休憩入りの時間が保存され、出勤ステータスが２(休憩中)に更新される
     * 【休憩戻】
     * restsテーブルに休憩終了時の時間が保存され、出勤ステータスが１(出勤中)に更新される
     *
     * @return RedirectResponse
     */
    public function rest() : RedirectResponse
    {
        $attendance = Attendance::with('rests')
            ->where('user_id',auth()->id())
            ->where('attendance_date',Carbon::today()->toDateString())
            ->first();

        $rest = Rest::where('attendance_id',$attendance->id)
            ->whereNull('rest_end')
            ->first();

        DB::transaction(function () use ($attendance,$rest){
            if(!$rest){
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'rest_start' => now(),
                ]);
                $attendance->update([
                    'status' =>2,
                ]);
            }else{
                $rest->update([
                    'rest_end' => now(),
                ]);
                $attendance->update([
                    'status' =>1,
                ]);
            }
        });
        return back();
    }

    /**
     * 一般スタッフ用のログインしているユーザーの月毎の勤怠の画面表示
     *
     * - サービスクラス(getMonthPeriod)からは、$preMonth(先月),$nextMonth(翌月),$targetMonth(当月)のデータをextract関数を使用し、取得
     * - サービスクラス(getMonthlyRecords)から一日の出勤表示に必要なデータ(出勤時間、休憩時間、労働時間など)を取得
     *
     * @param Request $request アクセスした時の月のデータ
     * @param AttendanceService $attendanceService サービスクラス
     *
     * @return View
     */
    public function list(Request $request,AttendanceService $attendanceService)
    {
        extract($attendanceService->getMonthPeriod($request));

        $records = $attendanceService->getMonthlyRecords($request);

        return view('staff.list',compact('records','preMonth','nextMonth','targetMonth'));
    }
}