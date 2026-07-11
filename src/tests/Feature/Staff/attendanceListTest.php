<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Services\AttendanceService;

class attendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        Rest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);
        $attendanceService = app(AttendanceService::class);
        $total_rest = $attendanceService->calculateRestTime($attendance);
        $total_work_time = $attendanceService->calculateActualWorkTime($attendance);

        $response = $this->actingAs($user)->get('/list');
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee($total_rest);
        $response->assertSee($total_work_time);
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/list');
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y/m'));
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/list');
        $response->assertStatus(200);

        $preMonth = now()->subMonthNoOverflow()->format('Y-m');

        $response = $this->get('/list?month='.$preMonth);
        $response->assertStatus(200);
        $response->assertSee(now()->subMonthNoOverflow()->format('Y/m'));
    }

    public function test_「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/list');
        $response->assertStatus(200);

        $nextMonth = now()->addMonthNoOverflow()->format('Y-m');

        $response = $this->get('/list?month='.$nextMonth);
        $response->assertStatus(200);
        $response->assertSee(now()->addMonthNoOverflow()->format('Y/m'));
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/list');
        $response->assertStatus(200);

        $response = $this->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
