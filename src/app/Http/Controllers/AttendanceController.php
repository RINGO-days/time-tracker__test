<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use App\Services\AttendanceService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


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
     * - 打刻画面において出勤ボタンを押すとattendancesテーブルに本日の勤怠のデータがない場合、新しくattendancesテーブルにデータを作成し、打刻画面に戻る。出勤ステータスはデフォルトは１(出勤中)
     * - 本日の出勤データがある場合では、打刻画面において退勤ボタンを押すと出勤ステータスを３(退勤済み)にアップデートし、打刻画面に戻る
     *
     * @return RedirectResponse
     */
    public function attendance() : RedirectResponse
    {
        $attendance = Attendance::where('user_id',auth()->id())
                ->where('attendance_date',Carbon::today()->toDateString())
                ->first();
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
        return back();
    }

    /**
     * - すでに出勤済みの状態(出勤ステータスが１)の場合、打刻画面において休憩入ボタンを押すと新しくrestsテーブルに休憩入りの時間が記録され、出勤ステータスが２(休憩中)にアップデートされる。
     * - 休憩中の場合、打刻画面の休憩戻ボタンを押すとrestsテーブルに休憩終了時の時間が記録され、出勤ステータスが１(出勤中)にアップデートされる。
     *
     * @return RedirectResponse
     */
    public function rest() : RedirectResponse
    {
        $attendance = Attendance::where('user_id',auth()->id())
                ->where('attendance_date',Carbon::today()->toDateString())
                ->first();

        $rest = Rest::where('attendance_id',$attendance->id)
                ->whereNull('rest_end')
                ->first();

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

        return back();
    }

    /**
     * 一般スタッフ用のログインしているユーザーの月毎の勤怠の画面表示
     * サービスクラス(getMonthPeriod)からは、$preMonth(先月),$nextMonth(翌月),$targetMonth(当月)のデータをextract関数を使用し、取得 　
     * サービスクラス(getMonthlyRecords)から一日の出勤表示に必要なデータ(出勤時間、休憩時間、労働時間など)を取得
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

    /**
     * 半年間のデータ($halfYearWorkTime)を取得し、それを元に様々な記録を計算するレポート画面を表示
     * - コレクションメソッドmapを用いて、データ全体の1日毎の実労働時間(サービスクラス,calculateActualWorkTimeで実労働時間の計算を行い文字列で返す)を分単位に戻し、その1日のデータの日付、労働時間(:分)、残業時間(480分超の時のみ、480分の減算、それ以外は0分)、遅刻、残業、早退、長時間労働をしたかをbool値で判別。($proceseedAttendance)
     * - 半年間の１件ずつの出勤データからそれぞれ長時間労働、早退、遅刻がtrueの日の回数のカウント
     * - 半年間の１件ずつの出勤データから半年間全ての労働時間、残業時間の合計の計算しレポート画面で表示する形式にフォーマット(例:10h 10m)
     * - 半年間の出勤の回数から1日の平均労働時間の算出(半年間で一回も出勤してない場合にゼロ除算を防ぐため、if文でエラーを回避)
     * - 半年間の月毎の配列(key名：20XX-XX)を作成し、それらにコレクションメソッドgroupByを用いて、配列のキー名と同じグループ名に編成($groupedMonths)
     * - それぞれの配列に月毎の労働時間、残業時間をコレクションメソッドsumで合計を出し、レポート画面での表示する形式にフォーマット
     *
     * @param AttendanceService $attendanceService サービスクラス 1日の実労働時間の計算
     * 
     * @return View
     */
    public function report(AttendanceService $attendanceService) : View
    {
        $halfYearWorkTime = Attendance::with('rests')
                            ->where('user_id',auth()->id())
                            ->whereBetween('attendance_date',[now()->submonth(5)->startOfMonth(),now()->endOfMonth()])
                            ->get();

        $proceseedAttendance = $halfYearWorkTime->map(function($day) use ($attendanceService){
            $total_time_str = $attendanceService->calculateActualWorkTime($day);
            $dayMinutes = !empty($total_time_str) ? (Carbon::parse($total_time_str)->hour * 60) + Carbon::parse($total_time_str)->minute : 0;

            return [
                'month' => Carbon::parse($day->attendance_date)->format('Y-m'),
                'minutes' => $dayMinutes,
                'over_minutes' => (!empty($day->leave_time) && $dayMinutes > 480) ? $dayMinutes - 480 : 0,
                'is_over_10h' => !empty($day->leave_time) && $dayMinutes > 600,
                'is_early' => !empty($day->leave_time) && Carbon::parse($day->leave_time)->isBefore(Carbon::parse($day->leave_time)->setTime(18,0,0)),
                'is_late' => Carbon::parse($day->attendance_time)->isAfter(Carbon::parse($day->attendance_time)->setTime(9,0,0)),
            ];
        });

        $over10hourCount = $proceseedAttendance->where('is_over_10h',true)->count();
        $earlyCount = $proceseedAttendance->where('is_early',true)->count();
        $lateCount = $proceseedAttendance->where('is_late',true)->count();

        $totalMinutes = $proceseedAttendance->sum('minutes');
        $totalOverTimeMinutes = $proceseedAttendance->sum('over_minutes');

        $formattedTotalWorkTime = ($totalMinutes / 60) . 'h ' . $totalMinutes % 60 . 'm';
        $formattedTotalOverTime = ($totalOverTimeMinutes / 60) . 'h ' . $totalOverTimeMinutes % 60 . 'm';
        if($halfYearWorkTime->isEmpty()){
            $formattedAvgTime = '0h 0m';
        }else{
            $avgTotalMinute = floor($totalMinutes / $halfYearWorkTime->count());
            $formattedAvgTime = floor($avgTotalMinute / 60).'h '. floor($avgTotalMinute) % 60 . 'm';
        }

        // キー名が20xx-xxのような６ヶ月分の空の連想配列を用意
        $monthlyData = collect(range(0,5))->mapWithKeys(function($i){
            return [now()->subMonths($i)->format('Y-m') => collect()];
        });

        $groupedMonths = $proceseedAttendance->groupBy('month');

        $monthlyTotalWorkTime = $monthlyData->map(function($value,$month) use($groupedMonths){
            $minutes = $groupedMonths->get($month)?->sum('minutes');
            return floor($minutes / 60) . 'h ' . floor($minutes % 60) . 'm';
        });

        $monthlyTotalOverTime = $monthlyData->map(function($value,$month) use($groupedMonths){
            $overMinutes = $groupedMonths->get($month)?->sum('over_minutes');
            return floor($overMinutes / 60) . 'h ' . floor($overMinutes % 60) . 'm';
        });

        return view('staff.attendanceReport',compact(
            'formattedTotalWorkTime',
            'formattedTotalOverTime',
            'formattedAvgTime',
            'monthlyData',
            'monthlyTotalWorkTime',
            'monthlyTotalOverTime',
            'over10hourCount',
            'earlyCount',
            'lateCount'
        ));
    }
}