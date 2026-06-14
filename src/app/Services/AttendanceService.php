<?php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Rest;
use App\Models\Attendance;

class AttendanceService
{
    // 
    public function getMonthPeriod(Request $request) : array
    {
        $targetMonth = $request->get('month',Carbon::today()->format('Y-m'));

        $startOfMonth = Carbon::parse($targetMonth)->startOfMonth();
        $endOfMonth = Carbon::parse($targetMonth)->endOfMonth();

        $preMonth = $startOfMonth->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $endOfMonth->copy()->addMonthNoOverflow()->format('Y-m');

        return [
            'targetMonth' => $targetMonth,
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
            'preMonth' => $preMonth,
            'nextMonth' => $nextMonth,
        ];
    }

    public function calculateRestTime(?Attendance $attendanceDay) :string
    {
        if(!$attendanceDay){
            return '';
        }
        $rests = Rest::where('attendance_id',$attendanceDay->id)
                    ->get();
        $rest_total = 0;
        foreach($rests as $rest){
            if($rest->rest_start && $rest->rest_end){
                $rest_start = Carbon::parse($rest->rest_start);
                $rest_end = Carbon::parse($rest->rest_end);

                $rest_total += $rest_start->diffInSeconds($rest_end);
            }
        }
        $rest_minutes = floor($rest_total / 60);
        $rest_hour = floor($rest_minutes / 60);
        $rest_minute = floor($rest_minutes % 60);

        $rest_time = sprintf('%02d:%02d',$rest_hour,$rest_minute);
        
        return $rest_time;
    }

    public function calculateActualWorkTime(?Attendance $attendanceDay) :string
    {
        if(!$attendanceDay || !$attendanceDay->leave_time){
            return '';
        }
        $work_start = Carbon::parse($attendanceDay->attendance_time);
        $work_end = Carbon::parse($attendanceDay->leave_time);

        $total_work_seconds = $work_start->diffInSeconds($work_end);

        $rest_time = Carbon::parse($this->calculateRestTime($attendanceDay));
        $rest_seconds = ($rest_time->hour * 3600) + ($rest_time->minute *60);

        $total_actual_seconds = $total_work_seconds - $rest_seconds;

        $total_actual_minutes = floor($total_actual_seconds / 60);
        $total_actual_hour = floor($total_actual_minutes / 60); 
        $total_actual_minute = floor($total_actual_minutes % 60);

        $total_actual_time = sprintf('%02d:%02d',$total_actual_hour,$total_actual_minute);

        return $total_actual_time;
    }

    public function getMonthlyRecords(Request $request) :array
    {
        extract($this->getMonthPeriod($request));

        if(auth()->user()->is_admin){
            $attendance_month = Attendance::where('user_id',$request->query('user_id'))
                                ->whereBetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                                ->get();
        }else{
            $attendance_month = Attendance::where('user_id',auth()->id())
                                ->whereBetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                                ->get();
        }

        $period = \Carbon\CarbonPeriod::create($startOfMonth,$endOfMonth);
        foreach($period as $date){
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
            $rest_time = $this->calculateRestTime($attendance_day);

            $actual_time = $this->calculateActualWorkTime($attendance_day);

            $records[] = [
                'date' => $dateString,
                'week' => $week,
                'attendance' => $attendance_time ? $attendance_time->format('H:i') : '',
                'leave' => $leave_time ? $leave_time->format('H:i') : '',
                'rest' => $rest_time,
                'actualTime' => $actual_time,
            ];
        }
        return $records;
    }
}