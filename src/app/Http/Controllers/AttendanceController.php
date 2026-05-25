<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
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
    }
    
    public function attendance(Request $request)
    {
        Attendance::create([
            'user_id' => auth()->id(),
            'status' => $request->status,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_time' => now(),
        ]);
        return redirect('/index');
    }


}
