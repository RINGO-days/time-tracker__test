<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function index()
    {
        $weeks = ['日','月','火','水','木','金','土'];
        $todayWeek = $weeks[now()->dayOfWeek];

        $todayAttendance = Attendance::where('user_id',auth()->id())
            ->where('attendance_date',Carbon::today()->toDateString())
            ->latest()->first();

        if(!$todayAttendance){
            $status = '勤務外';
        }else{
            switch($todayAttendance->status){
                case 1:
                    $status = '出勤中';
                    break;
                case 2:
                    $status = '休憩中';
                    break;
                case 3:
                    $status = '退勤済';
                    break;
            }
        }
        return view('staff.attendance',compact('status','todayWeek'));
    }

    public function attendance()
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
        return redirect('/');
    }

    public function rest()
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

        return redirect('/');
    }

    public function list(Request $request,AttendanceService $attendanceService)
    {
        extract($attendanceService->getMonthPeriod($request));

        $records = $attendanceService->getMonthlyRecords($request);
        $user = User::find($request->query('user_id'));

        return view('common.list',compact('records','preMonth','nextMonth','targetMonth','user'));
    }

    public function report(AttendanceService $attendanceService)
    {
        $halfYearWorkTime = Attendance::where('user_id',auth()->id())
                            ->whereBetween('attendance_date',[now()->submonth(5)->startOfDay(),now()->endOfDay()])
                            ->get();
        
        $totalMinutes = 0;
        $totalOverTimeMinutes = 0;
        $monthlyData = [];
        $lateCount = 0;
        $leaveEarlyCount = 0;
        $over10HourCount= 0;

        for($i = 5; $i >= 0; $i --){
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthlyData[$monthKey] = collect();
        }
        $monthlyLabel = $halfYearWorkTime->groupBy(function($monthly){
            return Carbon::parse($monthly->attendance_date)->format('Y-m');
        });
        foreach($monthlyLabel as $month => $days){
            $monthlyData[$month] = $days;
        }

        // 6ヶ月の総労働時間と月毎の労働時間の計算
        foreach($monthlyData as $month => $days){
            $monthlyTotalMinutes[$month] = 0;
            $monthlyTotalOverMinutes[$month] = 0;
            foreach($days as $dayWorkTime){
                $totalTimeStr = $attendanceService->calculateActualWorkTime($dayWorkTime);
                if(!empty($totalTimeStr)){
                    $dayMinutes = (Carbon::parse($totalTimeStr)->hour * 60) + (Carbon::parse($totalTimeStr)->minute);
                    $totalMinutes += $dayMinutes;
                    $monthlyTotalMinutes[$month] += $dayMinutes;
                    // 残業時間の計算
                    if (!empty($dayWorkTime->leave_time)) {
                        if($dayMinutes > 480){
                            $dayOvertimeMinutes = $dayMinutes - 480;
                            $totalOverTimeMinutes += $dayOvertimeMinutes;
                            $monthlyTotalOverMinutes[$month] += $dayOvertimeMinutes;
                        }

                        // 長時間労働回数（10時間超）のカウント
                        if($dayMinutes >= 600){
                            $over10HourCount ++;
                        }
                    }

                    // 早退回数のカウント
                    $work_end = Carbon::parse($dayWorkTime->leave_time);
                    $overtime_line = Carbon::parse($dayWorkTime->leave_time)->setTime(18,0,0);
                    if($work_end->lessThan($overtime_line)){
                        $leaveEarlyCount ++;
                    }

                }
                // 遅刻回数のカウント
                if(!empty($dayWorkTime->attendance_time)){
                    $lateness_line = Carbon::parse($dayWorkTime->attendance_time)->setTime(9,0,0);
                    $work_start = Carbon::parse($dayWorkTime->attendance_time);
                    if($work_start->greaterThan($lateness_line)){
                        $lateCount ++;
                    }
                }
            }
        }
        // 1日の平均労働時間の計算のための６ヶ月間の日数
        $startDay = now()->subMonths(5)->startOfDay();
        $endDay = now()->endOfDay();
        $totalDays = $endDay->diffInDays($startDay);


        return view('staff.attendanceReport',compact(
            'totalMinutes',
            'totalOverTimeMinutes',
            'totalDays',
            'halfYearWorkTime',
            'monthlyData',
            'monthlyTotalMinutes',
            'monthlyTotalOverMinutes',
            'lateCount',
            'leaveEarlyCount',
            'over10HourCount',
        ));
    }
}