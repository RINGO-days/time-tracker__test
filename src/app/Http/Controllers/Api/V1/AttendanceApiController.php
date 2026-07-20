<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use Illuminate\Support\Facades\Gate;
use App\Policies\AttendanceRecordPolicy;


class AttendanceApiController extends Controller
{
    /**
     * 勤怠記録の一覧を取得
     * 検索、ページネーション付きAPI
     *
     * - 送られてきたクエリパラメータに応じて勤怠データを絞り込む
     * - with('user','rests')により、関連するユーザー情報、休憩データも同時に取得
     * - リソースクラスにより整形されたデータをステータスコード200(ok)で表示
     * - 該当データがないときはステータスコード404(notFound)を表示
     *
     * 【検索クエリパラメータ】
     * - user_id : 特定のスタッフのIDで絞り込み
     * - date : 特定の日付(YYYY-MM-DD)で絞り込み
     * - month : 特定の月(YYYY-MM)で絞り込み
     * - page : ページ番号の指定
     * - per_page : １ページあたりの件数表示(デフォルト20、最大100)
     *
     * @param IndexAttendanceRecordRequest $request バリデーション済みのリクエスト
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

        return AttendanceRecordResource::collection($attendancesRecord_records);
    }

    /**
     * 勤怠を新規登録する
     *
     * - バリデーション済みのデータを元にログインユーザーの新しい勤怠レコードを作成
     * - 作成後の勤怠データからユーザー情報、休憩データをloadする
     * - リソースクラスによって整形されたデータをステータスコード201(created)で表示

     * @param  StoreAttendanceRecordReques $request リクエストフォルダによるバリデーション
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
     * 指定された勤怠レコードの表示
     *
     * - 動的セグメントにより勤怠IDのモデル情報を取得(ルートモデルバインディング)
     * - 取得した勤怠データに関連するユーザー情報と休憩データも同時にloadする
     * - リソースクラスによって整形されたデータをステータスコード200(ok)で表示
     * - 該当データがないときはステータスコード404(notFound)を表示
     *
     * @param  int  $attendanceRecord 勤怠IDのモデル情報を取得
     * @return \Illuminate\Http\Response
     */
    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'rests',
            'proposals'
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定した勤怠IDのデータの更新
     *
     * - 動的セグメントにより勤怠IDのモデル情報を取得(ルートモデルバインディング)
     * - ポリシーをチェックした上でバリデーション済みのデータで上書き更新する
     * - 更新した勤怠情報に関連するユーザー情報、休憩データを同時にloadする
     * - リソースクラスによって整形されたデータをステータスコード200(ok)で表示
     * - 該当データがないときはステータスコード404(notFound)を表示
     *
     * @param  UpdateAttendanceRecordRequest $request リクエストフォルダによるバリデーション
     * @param  int  $attendanceRecord 勤怠IDのモデル情報を取得
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
     * 指定した勤怠情報を削除
     *
     * - 動的セグメントにより勤怠IDのモデル情報を取得(ルートモデルバインディング)
     * - ポリシーをチェックした上で操作を実行
     * - 指定データを削除し、ステータスコード204(noContent)を表示
     *
     * @param  int  $attendanceRecord 勤怠IDのモデル情報を取得
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