<?php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Rest;
use App\Models\Attendance;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * 指定した月、または今月を基準とした１ヶ月間のデータを取得する
     *
     * - リクエストボディのクエリパラメータから対象の月を取得し、なければ今日の日付を選択する
     * - 得られた月を基準として開始日、終了日、翌月、先月の変数を算出
     *
     * @param Request $request クエリパラメータから基準月を取得
     *
     * @return array 算出された期間の配列
     */
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

    /**
     * 該当日の合計休憩時間を計算し、フォーマットした文字列(HH:mm)で取得する
     *
     * - 送られてきたリクエストボディから1日の複数回の休憩時間を合算し、分単位で算出する
     * - 求めた合計休憩時間(分)からHH:mm形式にフォーマットする
     * - 休憩中($in_resting = true)の場合は空文字('')を返す
     *
     * @param Attendance|null $attendandeDay 1日の勤怠情報(存在しない場合はnull)
     *
     * @return string (HH:mm)形式
     */
    public function calculateRestTime(?Attendance $attendanceDay) : string
    {
        if(!$attendanceDay){
            return '';
        }
        $rests = Rest::where('attendance_id',$attendanceDay->id)
                    ->get();
        $rest_total_minutes = 0;
        $in_resting = false;
        foreach($rests as $rest){
            if($rest->rest_start && $rest->rest_end){
                $rest_start = Carbon::parse($rest->rest_start);
                $rest_end = Carbon::parse($rest->rest_end);

                $rest_start_trunk = $rest_start->copy()->startOfMinute();
                $rest_end_trunk = $rest_end->copy()->startOfMinute();

                $rest_total_minutes += $rest_start_trunk->diffInMinutes($rest_end_trunk);
            }elseif($rest->rest_start && !$rest->rest_end){
                $in_resting = true;
            }
        }

        if($in_resting){
            return '';
        }
        $rest_hour = floor($rest_total_minutes / 60);
        $rest_minute = floor($rest_total_minutes % 60);

        $rest_time = sprintf('%02d:%02d',$rest_hour,$rest_minute);

        return $rest_time;
    }

    /**
     * 該当日の実労働時間を計算し、フォーマットした文字列(HH:mm)で取得する
     *
     * - 送られてきたリクエストボディから出勤から退勤までの純労働時間を分単位で算出する
     * - 別途定義された休憩時間算出ロジックを使い、同じく分単位で合計の休憩時間を求める
     * - 純労働時間から休憩時間を引いた実労働時間を算出する
     * *勤怠データがない場合、または退勤時間が未入力（勤務中）の場合は空文字（''）を返す
     *
     * @param Attendance|null $attendanceDay 1日の勤怠情報
     *
     * @return string (HH:mm)形式
     */
    public function calculateActualWorkTime(?Attendance $attendanceDay) : string
    {
        if(!$attendanceDay || !$attendanceDay->leave_time){
            return '';
        }
        $work_start = Carbon::parse($attendanceDay->attendance_time);
        $work_end = Carbon::parse($attendanceDay->leave_time);

        $work_start_trunk = $work_start->copy()->startOfMinute();
        $work_end_trunk = $work_end->copy()->startOfMinute();

        $total_work_minutes = $work_start_trunk->diffInMinutes($work_end_trunk);

        $rest_minutes = 0;
        $rest_time = Carbon::parse($this->calculateRestTime($attendanceDay));
        $rest_minutes = ($rest_time->hour * 60) + ($rest_time->minute);

        $total_actual_minutes = $total_work_minutes - $rest_minutes;

        $total_actual_hour = floor($total_actual_minutes / 60);
        $total_actual_minute = floor($total_actual_minutes % 60);

        $total_actual_time = sprintf('%02d:%02d',$total_actual_hour,$total_actual_minute);

        return $total_actual_time;
    }

    /**
     * １ヶ月間の勤怠情報を取得する
     *
     * - ログインユーザーが管理者だった場合、送られてきたIDのユーザー情報を、スタッフだったら自分のユーザーIDの１ヶ月の勤怠情報を取得
     * - 別途定義した月を指定する変数を利用し、CarbonPeriodで１ヶ月間の期間を指定
     * - １日ずつ勤怠ID、日付(曜日)、出勤時間、退勤時間、合計休憩時間、実労働時間を配列形式で取得
     * *出勤データがない日は空白行として配列に含む
     *
     * @param Request $request クエリパラメータから対象の月を取得
     *
     * @return array
     */
    public function getMonthlyRecords(Request $request) : array
    {
        extract($this->getMonthPeriod($request));

        if(auth()->user()->is_admin){
            $attendance_month = Attendance::where('user_id',$request->id)
                                ->whereBetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                                ->get();
        }else{
            $attendance_month = Attendance::where('user_id',auth()->id())
                                ->whereBetween('attendance_date',[$startOfMonth->toDateString(),$endOfMonth->toDateString()])
                                ->get();
        }

        $period = CarbonPeriod::create($startOfMonth,$endOfMonth);

        return collect($period)->map(function ($date) use ($attendance_month){
            $dateString = $date->toDateString();
            $weeks = ['日','月','火','水','木','金','土'];
            $week = $weeks[$date->dayOfWeek];

            $attendance_day = $attendance_month->where('attendance_date',$dateString)->first();
            $attendance_time = $attendance_day ? Carbon::parse($attendance_day->attendance_time) : '';
            $leave_time = $attendance_day ? Carbon::parse($attendance_day->leave_time) : '';

            if($attendance_time && $leave_time){
                $work_seconds = $attendance_time->diffInSeconds($leave_time);
                $work_minutes = floor($work_seconds / 60);
                $hour = floor($work_minutes / 60);
                $minute = $work_minutes % 60;
                $working_time = sprintf('%02d:%02d', $hour, $minute);
            }else{
                $working_time = '';
            }

            $rest_time = $this->calculateRestTime($attendance_day);
            $actual_time = $this->calculateActualWorkTime($attendance_day);
            return [
                'attendance_id' => $attendance_day ? $attendance_day->id : '',
                'date' => $dateString,
                'week' => $week,
                'attendance' => $attendance_time ? $attendance_time->format('H:i') : '',
                'leave' => $leave_time ? $leave_time->format('H:i') : '',
                'rest' => $rest_time,
                'actualTime' => $actual_time,
            ];
        })->all();
    }

    /**
     * 勤怠修正申請の作成(および管理者による即時反映、自動承認処理)
     * DBトランザクションによりデータ不整合を防ぐ
     *
     * - 勤怠詳細のフォーム画面から送られてきたそれぞれの修正内容をproposalsテーブルに保存
     * - 管理者の場合、修正申請の保存と同時に、実際の勤怠•休憩データへ内容を即時上書き(レコードがなければ新規作成)する
     * - 新規作成時は生成された勤怠IDをproposalsテーブルのattendance_idに紐付けを行う
     * - 最後に修正申請データのステータスを２(承認済み)に更新する
     *
     * @param Request $request 勤怠詳細画面の修正申請のフォームデータ
     * @param User $user 修正対象のスタッフ
     *
     * @return Proposal $proposal 作成された修正申請データ
     */
    public function createAttendanceDetailProposal(Request $request, User $user) : Proposal
    {
        $proposal_attendance = [
            'attendance_time' => $request->attendance,
            'leave_time' => $request->leave,
        ];

        return DB::transaction(function () use ($proposal_attendance, $request, $user){
            $proposal = Proposal::create([
                'user_id' => $user->id,
                'target_date' => $request->date,
                'proposed_attendance' => $proposal_attendance ?? null,
                'proposed_rest' => $proposal_rest ?? null,
                'remarks' => $request->remarks,
            ]);

            if(auth()->user()->is_admin){
                $attendance = Attendance::with('rests')
                            ->where('user_id',$proposal->user_id)
                            ->where('attendance_date',$proposal->target_date)
                            ->first();

                if($attendance){
                    $attendance->update([
                        'attendance_time' => $proposal_attendance['attendance_time'],
                        'leave_time' => $proposal_attendance['leave_time'],
                    ]);
                }else{
                    $attendance = Attendance::create([
                        'user_id' => $proposal->user_id,
                        'attendance_date' => $proposal->target_date,
                        'attendance_time' => $proposal_attendance['attendance_time'],
                        'leave_time' => $proposal_attendance['leave_time'],
                    ]);
                    $proposal->update([
                        'attendance_id' => $attendance->id,
                    ]);
                }

                $proposal_rests = $request->rest;
                if($proposal_rests){
                    foreach($proposal_rests as $restData){
                        if(!empty($restData['rest_id']) && empty($restData['rest_start'])){
                            $attendance->rests()
                                ->where('id',$restData['rest_id'])
                                ->delete();
                        }
                        if(empty($restData['rest_start'])){
                            continue;
                        }

                        if(!empty($restData['rest_id'])){
                            Rest::where('id',$restData['rest_id'])
                                ->update([
                                    'rest_start' => $restData['rest_start'],
                                    'rest_end' => $restData['rest_end'],
                                ]);
                        }else{
                            Rest::create([
                                'attendance_id' => $attendance->id,
                                'rest_start' => $restData['rest_start'],
                                'rest_end' => $restData['rest_end'],
                            ]);
                        }
                    }
                }
                $proposal->update([
                    'status' => 2
                ]);
            }
            return $proposal;
        });
    }
}