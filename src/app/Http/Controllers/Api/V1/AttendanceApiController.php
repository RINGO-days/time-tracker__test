<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Validation\Rule;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;

class AttendanceApiController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum')->expect(['index','show']);
    // }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page',20);
        if($perPage > 100){
            $perPage = 100;
        }

        $query = Attendance::query();
        if($request->query('user_id')){
            $query->where('user_id',$request->query('user_id'));
        }

        if($request->query('date')){
            $query->whereDate('attendance_date',$request->query('date'));
        }

        if($request->query('month')){
            $query->whereMonth('attendance_date',$request->query('month'));
        }
        $attendances = $query->paginate($perPage);
        if($attendances->isEmpty()){
            return response()->json([
                'message' => '出勤記録がありませんでした。',
                'error' => 'RECORDS_NOT_FOUND'
            ],404);
        }
        return response()->json([
            'data' => $attendances
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $attendance = Attendance::create([
            'user_id' => $request->user()->id,
            'attendance_date' => $request->input('date'),
            'attendance_time' => $request->input('clock_in'),
            'leave_time' => $request->input('clock_out'),
            'comment' => $request->input('comment')
        ]);

        return response()->json($attendance,201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$attendanceRecord)
    {
        $attendance = Attendance::with([
            'user',
            'rests',
            'proposals'
        ])
        ->find($id);

        if(!$attendance){
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。'
            ],404);
        }

        return response()->json($attendance);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
