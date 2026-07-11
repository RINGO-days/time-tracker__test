<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class statusTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_勤務外の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 1
        ]);
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'leave_time' => null,
            'status' => 2
        ]);
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 3
        ]);
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
