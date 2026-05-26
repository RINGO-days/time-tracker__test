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
        $weeks = ['月','火','水','木','金','土','日'];
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

    public function list()
    {
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();

        $attendances = Attendance::where('user_id',auth()->id())
                ->wherebetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                ->get();

        for($date = $startOfMonth->copy();$date->lte($endOfMonth);$date->addDay()){
            $dateString = $date->toDateString();

            $attendance = $attendances->where('attendance_date',$dateString)->where('status',1)->first();
            $leave = $attendances->where('attendance_date',$dateString)->where('status',3)->first();
            if($attendance && $leave){
                $workingTimes = $attendance->check_time->diffInMinutes($leave->check_time);
            }else{
                $workingTimes = '';
            }
            $rest_start = $attendances->where('attendance_date',$dateString)->where('status',2)->first();
            $rest_end = $attendances->where('attendance_date',$dateString)->where('status',1)->first();

            $recodes[] = [
                'date' => $date->format('m/d'),
                'attendance' => $attendance ? $attendance->check_time->format('H:i') : '',
                'leave' => $leave ? $leave->check_time->format('H:i') : '',
                'workingTimes' => $workingTimes,
            ];
        }
        return view('common.list',compact('recodes'));
    }

}
