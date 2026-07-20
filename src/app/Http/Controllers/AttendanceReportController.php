<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\View\View;
use App\Services\AttendanceService;
use Carbon\Carbon;


class AttendanceReportController extends Controller
{
    /**
     * 半年間のデータ($halfYearWorkTime)を取得し、それを元に様々な記録を計算するレポート画面を表示
     *
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
    public function report(AttendanceService $attendanceService): View
    {
        $halfYearWorkTime = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereBetween('attendance_date', [now()->submonth(5)->startOfMonth(), now()->endOfMonth()])
            ->get();

        $proceseedAttendance = $halfYearWorkTime->map(function ($day) use ($attendanceService) {
            $total_time_str = $attendanceService->calculateActualWorkTime($day);
            $dayMinutes = !empty($total_time_str) ? (Carbon::parse($total_time_str)->hour * 60) + Carbon::parse($total_time_str)->minute : 0;

            return [
                'month' => Carbon::parse($day->attendance_date)->format('Y-m'),
                'minutes' => $dayMinutes,
                'over_minutes' => (!empty($day->leave_time) && $dayMinutes > 480) ? $dayMinutes - 480 : 0,
                'is_over_10h' => !empty($day->leave_time) && $dayMinutes > 600,
                'is_early' => !empty($day->leave_time) && Carbon::parse($day->leave_time)->isBefore(Carbon::parse($day->leave_time)->setTime(18, 0, 0)),
                'is_late' => Carbon::parse($day->attendance_time)->isAfter(Carbon::parse($day->attendance_time)->setTime(9, 0, 0)),
            ];
        });

        $over10hourCount = $proceseedAttendance->where('is_over_10h', true)->count();
        $earlyCount = $proceseedAttendance->where('is_early', true)->count();
        $lateCount = $proceseedAttendance->where('is_late', true)->count();

        $totalMinutes = $proceseedAttendance->sum('minutes');
        $totalOverTimeMinutes = $proceseedAttendance->sum('over_minutes');

        $formattedTotalWorkTime = ($totalMinutes / 60) . 'h ' . $totalMinutes % 60 . 'm';
        $formattedTotalOverTime = ($totalOverTimeMinutes / 60) . 'h ' . $totalOverTimeMinutes % 60 . 'm';
        if ($halfYearWorkTime->isEmpty()) {
            $formattedAvgTime = '0h 0m';
        } else {
            $avgTotalMinute = floor($totalMinutes / $halfYearWorkTime->count());
            $formattedAvgTime = floor($avgTotalMinute / 60) . 'h ' . floor($avgTotalMinute) % 60 . 'm';
        }

        // キー名が20xx-xxのような６ヶ月分の空の連想配列を用意
        $monthlyData = collect(range(0, 5))->mapWithKeys(function ($i) {
            return [now()->subMonths($i)->format('Y-m') => collect()];
        });

        $groupedMonths = $proceseedAttendance->groupBy('month');

        $monthlyTotalWorkTime = $monthlyData->map(function ($value, $month) use ($groupedMonths) {
            $minutes = $groupedMonths->get($month)?->sum('minutes');
            return floor($minutes / 60) . 'h ' . floor($minutes % 60) . 'm';
        });

        $monthlyTotalOverTime = $monthlyData->map(function ($value, $month) use ($groupedMonths) {
            $overMinutes = $groupedMonths->get($month)?->sum('over_minutes');
            return floor($overMinutes / 60) . 'h ' . floor($overMinutes % 60) . 'm';
        });

        return view('staff.attendanceReport', compact(
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

