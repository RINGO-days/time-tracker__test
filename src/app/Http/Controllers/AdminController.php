<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;


class AdminController extends Controller
{
    public function dailyAttendance(Request $request)
    {
        $targetDay = Carbon::parse($request->get('day',Carbon::today()->toDateString()));
        $preDay = Carbon::parse($targetDay)->copy()->subDay()->format('Y/m/d');
        $nextDay = Carbon::parse($targetDay)->copy()->addDay()->format('Y/m/d');

        // 指定した日の勤務記録を全件取得
        $dailyAttendances = Attendance::with(['user','rests'])
                            ->where('attendance_date',$targetDay)
                            ->get();

        foreach($dailyAttendances as $dailyAttendance){
            // 休憩時間の合計の計算
            $total_rest_seconds = 0;
            foreach($dailyAttendance->rests as $rest){
                if($rest->rest_start && $rest->rest_end){
                    $rest_start = Carbon::parse($rest->rest_start);
                    $rest_end = Carbon::parse($rest->rest_end);
                    $total_rest_seconds += $rest_start->diffInSeconds($rest_end);
                }
            };
            $total_rest_minutes = floor($total_rest_seconds / 60);
            $rest_minute = floor($total_rest_minutes % 60);
            $rest_hour = floor($total_rest_minutes / 60);
            $dailyAttendance->rest_total = sprintf('%02d:%02d',$rest_hour,$rest_minute);

            // 労働時間から休憩時間を引いた実労働時間の計算
            if($dailyAttendance->attendance_time && $dailyAttendance->leave_time){
                $work_start = Carbon::parse($dailyAttendance->attendance_time);
                $work_end = Carbon::parse($dailyAttendance->leave_time);

                $total_work_seconds = $work_start->diffInSeconds($work_end);
                $total_work_minutes = floor($total_work_seconds / 60);


                $actual_work_minutes = $total_work_minutes - $total_rest_minutes;
                $actual_work_minute = floor($actual_work_minutes % 60);
                $actual_work_hour = floor($actual_work_minutes / 60);

                $dailyAttendance->actual_work_time = sprintf('%02d:%02d',$actual_work_hour,$actual_work_minute);
            }
        }


        return view('admin.dailyAttendance',compact('targetDay','preDay','nextDay','dailyAttendances'),['nav' => 'admin']);
    }
}
