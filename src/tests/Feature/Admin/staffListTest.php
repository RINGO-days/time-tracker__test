<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Services\attendanceService;

class staffListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる()
    {
        $user1 = User::factory()->create([
            'name' => 'staffUser1',
            'email' => 'user1@example.com'
        ]);
        $user2 = User::factory()->create([
            'name' => 'staffUser2',
            'email' => 'user2@example.com'
        ]);
        $user3 = User::factory()->create([
            'name' => 'staffUser3',
            'email' => 'user3@example.com'
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/staff/list');
        $response->assertStatus(200);
        $response->assertSee('staffUser1');
        $response->assertSee('user1@example.com');
        $response->assertSee('staffUser2');
        $response->assertSee('user2@example.com');
        $response->assertSee('staffUser3');
        $response->assertSee('user3@example.com');
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => '12:00',
            'rest_end' => '13:00'
        ]);
        $attendanceService = app(attendanceService::class);
        $total_rest = $attendanceService->calculateRestTime($attendance);
        $total_work = $attendanceService->calculateActualWorkTime($attendance);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/staff/'.$staff->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee($total_rest);
        $response->assertSee($total_work);
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $preMonth = now()->subMonthNoOverflow()->format('Y-m');

        $response = $this->actingAs($adminUser)->get("/admin/attendance/staff/{$staff->id}?month={$preMonth}");
        $response->assertStatus(200);
        $response->assertSee(now()->subMonthNoOverflow()->format('Y/m'));
    }
    public function test_「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $nextMonth = now()->addMonthNoOverflow()->format('Y-m');

        $response = $this->actingAs($adminUser)->get("/admin/attendance/staff/{$staff->id}?month={$nextMonth}");
        $response->assertStatus(200);
        $response->assertSee(now()->addMonthNoOverflow()->format('Y/m'));
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $staff = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => 1
        ]);

        $nextMonth = now()->addMonthNoOverflow()->format('Y-m');

        $response = $this->actingAs($adminUser)->get('/admin/attendance/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
