<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use App\Models\Proposal;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AttendanceService;


class AdminController extends Controller
{
    public function dailyAttendance(Request $request,AttendanceService $attendanceService)
    {
        $targetDay = Carbon::parse($request->get('day',Carbon::today()->toDateString()));
        $preDay = Carbon::parse($targetDay)->copy()->subDay()->format('Y/m/d');
        $nextDay = Carbon::parse($targetDay)->copy()->addDay()->format('Y/m/d');

        $dailyAttendances = Attendance::with(['user','rests'])
                            ->where('attendance_date',$targetDay)
                            ->get();

        foreach($dailyAttendances as $dailyAttendance){
            foreach($dailyAttendance->rests as $rest){
                if($rest->rest_start && $rest->rest_end){
                    $dailyAttendance->rest_total_str = $attendanceService->calculateRestTime($dailyAttendance);
                }
            };

            if($dailyAttendance->attendance_time && $dailyAttendance->leave_time){
                $dailyAttendance->actual_work_time_str = $attendanceService->calculateActualWorkTime($dailyAttendance);
            }
        }

        return view('admin.dailyAttendance',compact('targetDay','preDay','nextDay','dailyAttendances'),['nav' => 'admin']);
    }

    public function editDetail(Request $request,$id)
    {
        $attendance = Attendance::with('user')
                    ->find($id);

        $rests = Rest::where('attendance_id',$attendance->id)
                    ->get();

        $details = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'date' => $attendance->attendance_date,
            'attendance' => $attendance->attendance_time->format('H:i'),
            'leave' => $attendance->leave_time ? $attendance->leave_time->format('H:i') : '',
        ];
        return view('common.detail',compact('details','rests'),['nav' => 'admin']);
    }

    public function staffList()
    {
        $users = User::all();
        return view('admin.staffList',compact('users'),['nav' => 'admin']);
    }

    public function staffMonthlyAttendance(Request $request,AttendanceService $attendanceService,$id)
    {
        extract($attendanceService->getMonthPeriod($request));
        $records = $attendanceService->getMonthlyRecords($request);
        $user = User::find($id);

        return view('admin.staffMonthlyAttendance',compact('records','preMonth','nextMonth','targetMonth','user'),['nav' => 'admin']);
    }

    public function export(Request $request,AttendanceService $attendanceService)
    {
        extract($attendanceService->getMonthPeriod($request));
        $user = User::find($request->id);

        $fileName = $user->name . 'さんの勤怠＿' . $targetMonth . '.csv';

        $response = new StreamedResponse(function() use($startOfMonth,$endOfMonth,$attendanceService,$request){
            $stream = fopen('php://output','w');

            fwrite($stream,pack('C*', 0xEF, 0xBB, 0xBF));

            $header = ['日付','出勤','退勤','休憩','合計'];
            fputcsv($stream,$header);

            $records = $attendanceService->getMonthlyRecords($request);

            foreach($records as $row)
                fputcsv($stream,[
                    $row['date']."[".$row['week']."]",
                    $row['attendance'],
                    $row['leave'],
                    $row['rest'],
                    $row['actualTime'],
                ]);

            fclose($stream);
            });
        $response->headers->set('Content-Type','text/csv');
        $response->headers->set('Content-Disposition','attachment; filename="' . rawurlencode($fileName) . '"');
        return $response;
    }

    public function requestShow($attendance_correct_request_id)
    {
        $proposal = Proposal::with(['user','attendance.rests'])->findOrFail($attendance_correct_request_id);

        return view('admin.correctionApprove',compact('proposal'),['nav' => 'admin']);
    }

    public function approve(Request $request,$attendance_correct_request_id)
    {
        $proposal = Proposal::find($attendance_correct_request_id);

        $proposal->attendance->update([
            'attendance_time' => $proposal->proposed_attendance['attendance_time'],
            'leave_time' => $proposal->proposed_attendance['leave_time'],
        ]);

        foreach($proposal->proposed_rest as $proposalRest){
            if(!empty($proposalRest['rest_id'])){
                Rest::where('id',$proposalRest['rest_id'])
                    ->update([
                        'rest_start' => $proposalRest['rest_start'],
                        'rest_end' => $proposalRest['rest_end'],
                    ]);
            }else{
                Rest::create([
                    'attendance_id' => $proposal->attendance->id,
                    'rest_start' => $proposalRest['rest_start'],
                    'rest_end' => $proposalRest['rest_end'],
                ]);
            }
        }
        $proposal->update([
            'status' => 2
        ]);

        return back();
    }
}
