<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class attendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_勤怠詳細画面の「名前」がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create([
            'name' => 'test_user'
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee('test_user');
    }

    public function test_勤怠詳細画面の「日付」が選択した日付になっている()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y年'));
        $response->assertSee(now()->format('n月j日'));
    }

    public function test_「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_「休憩」にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);
        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => '12:00:00',
            'rest_end' => '13:00:00'
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
