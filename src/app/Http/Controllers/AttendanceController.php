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

        return view('staff.list',compact('records','preMonth','nextMonth','targetMonth'));
    }

    public function report(AttendanceService $attendanceService)
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
            $avgTotalMinute = $totalMinutes / $halfYearWorkTime->count();
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
            $overMinutes = $groupedMonths->get($month)->sum('over_minutes');
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