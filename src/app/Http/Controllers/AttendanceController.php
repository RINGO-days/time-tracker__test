<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

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

    public function list(Request $request)
    {
        $targetMonth = $request->get('month',Carbon::today()->format('Y-m'));
        $startOfMonth = Carbon::parse($targetMonth)->startOfMonth();
        $endOfMonth = Carbon::parse($targetMonth)->endOfMonth();

        $preMonth = $startOfMonth->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $endOfMonth->copy()->addMonthNoOverflow()->format('Y-m');

        $attendance_month = Attendance::where('user_id',auth()->id())
                            ->whereBetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                            ->get();

        for($date = $startOfMonth->copy() ; $date->lte($endOfMonth) ; $date->addDay()){
            // 日付の文字列化、並びに曜日の出力
            $dateString = $date->toDateString();
            $weeks = ['日','月','火','水','木','金','土'];
            $week = $weeks[$date->dayOfWeek];

            // 出勤と退勤時刻
            $attendance_day = $attendance_month->where('attendance_date',$dateString)->first();
            $attendance_time = $attendance_day ? Carbon::parse($attendance_day->attendance_time) : '';
            $leave_time = $attendance_day ? Carbon::parse($attendance_day->leave_time) : '';

            // 勤務時間の計算
            if($attendance_time && $leave_time){
                $work_seconds = $attendance_time->diffInSeconds($leave_time);
                $work_minutes = floor($work_seconds / 60);
                $hour = floor($work_minutes / 60);
                $minute = $work_minutes % 60;
                $working_time = sprintf('%02d:%02d', $hour, $minute);
            }else{
                $working_time = '';
            };

            // 休憩時間の複数回の合計の計算
            $rest_total = 0;
            if($attendance_day){
                $rests = Rest::where('attendance_id',$attendance_day->id)
                            ->get();
                foreach($rests as $rest){
                    if($rest->rest_start && $rest->rest_end){
                        $rest_start = Carbon::parse($rest->rest_start);
                        $rest_end = Carbon::parse($rest->rest_end);

                        $rest_total += $rest_start->diffInSeconds($rest_end);
                    };
                };
                $rest_minutes = round($rest_total / 60);
                $rest_hour = floor($rest_minutes / 60);
                $rest_minute = $rest_minutes % 60;
                $rest_time = sprintf('%02d:%02d',$rest_hour,$rest_minute);
            }else{
                $rest_time = '';
            }

            $records[] = [
                'date' => $dateString,
                'week' => $week,
                'attendance' => $attendance_time ? $attendance_time->format('H:i') : '',
                'leave' => $leave_time ? $leave_time->format('H:i') : '',
                'workingTime' => $working_time,
                'rest' => $rest_time,
            ];
        }

        return view('common.list',compact('records','preMonth','nextMonth','targetMonth'));
    }
}