<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Services\attendanceService;

class attendanceReportTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_ゲストはレポートページにアクセスできない()
    {
        $response = $this->get('/attendance/report');
        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される()
    {
        $user = User::factory()->create();
        // restのfactoryは12:00~13:00 の休憩のデータ（実労働時間：10時間、残業時間/日：２時間、休憩時間：１時間）
        $attendances[] = Attendance::factory()->has(Rest::factory())->create([
            'user_id' => $user->id,
            'attendance_date' => now()->subMonthNoOverflow()->toDateString(),
            'attendance_time' => '09:00',
            'leave_time' => '20:00'
        ]);
        $attendances[] = Attendance::factory()->has(Rest::factory())->create([
            'user_id' => $user->id,
            'attendance_date' => now()->subMonthNoOverflow(2)->toDateString(),
            'attendance_time' => '09:00',
            'leave_time' => '20:00'
        ]);
        $attendances[] = Attendance::factory()->has(Rest::factory())->create([
            'user_id' => $user->id,
            'attendance_date' => now()->subMonthNoOverflow(3)->toDateString(),
            'attendance_time' => '09:00',
            'leave_time' => '20:00'
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');
        $response->assertStatus(200);
        $response->assertSee('30h 0m');
        $response->assertSee('2h 0m');
        $response->assertSee('10h 0m');
    }

    public function test_勤怠記録がないユーザーで安全に処理される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/report');
        $response->assertStatus(200);
        $response->assertSee('0h 0m');
    }
}
