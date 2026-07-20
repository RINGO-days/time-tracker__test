<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class UserTableSeeder extends Seeder
{
    /**
     * ユーザーのダミーデータ投入
     *
     * - for($i)で現在から６ヶ月前までを指定
     * - dayInMonthで対象の月の日数を取得し、weekend()で土日以外を指定する
     * - 当月のみ17日分のデータを入れ、それ以外の月は15日分のデータを挿入
     * 【投入データ】
     * **通常勤怠データ**
     * 出勤時間：09:00 | 退勤時間：18:00 | 休憩入り：12:00 | 休憩戻り：13:00 |
     * 上記を当月を除いた過去５ヶ月のデータとして各月15日分を挿入
     *
     * **当月のみ**
     * 通常出勤データ ×10
     * 残業データ（09:00~20:00）×3
     * 遅刻データ（09:30~18:00）×2
     * 早退データ（09:00~17:00）×1
     * 長時間労働データ（09:00~21:00）×1
     * 上記をランダムで挿入する
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => 'ユーザー1（一般）',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => 0
        ];
        DB::table('users')->insert($param);

        $user = User::first();
        for($i = 0; $i <= 5; $i ++){
            $targetMonth = Carbon::now()->subMonths($i);
            $daysInMonth = $targetMonth->daysInMonth;
            $weekdays = [];
            for($day = 1; $day <= $daysInMonth;$day ++){
                $date = Carbon::create($targetMonth->year,$targetMonth->month,$day);
                if(!$date->isWeekend()){
                    $weekdays[] = $date->format('Y-m-d');
                }
            }
            $dayCount = ($i === 0) ? 17 : 15;
            $selectDays = collect($weekdays)->random($dayCount);

            $timePatterns = [];
            if($i === 0){
                for($c = 0; $c < 10; $c ++){
                    $timePatterns[] = [
                        'clock_in' => '09:00:00',
                        'clock_out' => '18:00:00'
                    ];
                }
                for($c = 0; $c < 3; $c ++){
                    $timePatterns[] = [
                        'clock_in' => '09:00:00',
                        'clock_out' => '20:00:00'
                    ];
                }
                for($c = 0; $c < 2; $c ++){
                    $timePatterns[] = [
                        'clock_in' => '09:30:00',
                        'clock_out' => '18:00:00'
                    ];
                }
                $timePatterns[] = [
                    'clock_in' => '09:00:00',
                    'clock_out' => '17:00:00'
                ];
                $timePatterns[] = [
                    'clock_in' => '08:00:00',
                    'clock_out' => '21:00:00'
                ];
                shuffle($timePatterns);
            }
            foreach($selectDays as $key => $workDay){
                $clockIn = '09:00:00';
                $clockOut = '18:00:00';
                if($i === 0){
                    $clockIn = $timePatterns[$key]['clock_in'];
                    $clockOut = $timePatterns[$key]['clock_out'];
                }
                $attendanceId = DB::table('attendances')->insertGetId([
                    'user_id' => $user->id,
                    'attendance_date' => $workDay,
                    'attendance_time' => $clockIn,
                    'leave_time' => $clockOut,
                    'status' => 3,
                ]);
                DB::table('rests')->insert([
                    'attendance_id' => $attendanceId,
                    'rest_start' => '12:00:00',
                    'rest_end' => '13:00:00'
                ]);
            }
        }

        $param = [
            'name' => 'ユーザー2（一般）',
            'email' => 'user2@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => 0
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'ユーザー3（一般）',
            'email' => 'user3@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => 1
        ];
        DB::table('users')->insert($param);
    }
}
