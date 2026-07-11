<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class clockOutTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'attendance_date' => now()->toDateString(),
            'leave_time' => null,
            'status' => 1
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('<button class="attendance-button" type="submit" formaction="/attendance">退勤</button>',false);

        $response = $this->post('/attendance');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('退勤済');

        $response = $this->get('/list');
        $response->assertStatus(200);
        $response->assertSee(now()->format('H:i'));
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 9, 9, 0, 0));

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $response = $this->post('/attendance');
        $response->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2026, 7, 9, 18, 0, 0));

        $response = $this->post('/attendance');
        $response->assertStatus(302);

        $response = $this->get('/list');
        $response->assertStatus(200);
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
