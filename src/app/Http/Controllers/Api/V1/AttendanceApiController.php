<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Policies\AttendanceRecordPolicy;


class AttendanceApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $perPage = $request->query('per_page',20);

        $query = Attendance::query();

        $query->when($request->query('user_id'),fn($query,$value) => $query->where('user_id',$value));

        $query->when($request->query('date'),fn($query,$value) => $query->whereDate('attendance_date',$value));

        $query->when($request->query('month'),function($query,$value){
            $parts = explode('-',$value);
            if(count($parts) === 2){
                $year = $parts[0];
                $month = $parts[1];

                $query->whereYear('attendance_date',$year)
                        ->whereMonth('attendance_date',$month);
            }
        });

        $attendancesRecord_records = $query->with(['user','rests'])
                        ->latest('attendance_date')
                        ->paginate($perPage);

        if($attendancesRecord_records->isEmpty()){
            return response()->json([
                'message' => '出勤記録がありませんでした。',
            ],404);
        }

        return AttendanceRecordResource::collection($attendancesRecord_records);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();

        $attendanceRecord = $request->user()->attendances()->create([
            'attendance_date' => $validated['date'],
            'attendance_time' => $validated['clock_in'],
            'leave_time' => $validated['clock_out'] ?? null,
            'comment' => $validated['comment'] ?? null
        ]);

        $attendanceRecord->load([
            'user',
            'rests'
        ]);
        return (new AttendanceRecordResource($attendanceRecord))
                ->response()
                ->setStatuscode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'rests',
            'proposals'
        ]);

        if(!$attendanceRecord){
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。'
            ],404);
        }

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttendanceRecordRequest $request,Attendance $attendanceRecord)
    {
        // 勤怠記録のモデル名がAttendanceのため、直接AttendanceRecordPolicyをGate::policyで指定
        Gate::policy(Attendance::class, AttendanceRecordPolicy::class);

        $this->authorize('update',$attendanceRecord);

        $validated = $request->validated();
        $attendanceRecord->update([
            'attendance_date' => $validated['date'],
            'attendance_time' => $validated['clock_in'],
            'leave_time' => $validated['clock_out'] ?? null,
            'comment' => $validated['comment'] ?? null
        ]);

        $attendanceRecord->load([
            'user',
            'rests'
        ]);
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attendance $attendanceRecord)
    {
        // 勤怠記録のモデル名がAttendanceのため、直接AttendanceRecordPolicyをGate::policyで指定
        Gate::policy(Attendance::class, AttendanceRecordPolicy::class);

        $this->authorize('delete',$attendanceRecord);

        $attendanceRecord->delete();
        return response()->json(null, 204);
    }
}