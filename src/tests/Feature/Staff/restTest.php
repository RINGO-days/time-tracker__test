<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class restTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_休憩ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'leave_time' => null,
            'status' => 1
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('<button class="rest-button" type="submit" formaction="/rest">休憩入</button>',false);

        $response = $this->post('/rest');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'leave_time' => null,
            'status' => 1
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $response = $this->post('/rest');
        $response->assertStatus(302);

        $response = $this->post('/rest');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertSee('<button class="rest-button" type="submit" formaction="/rest">休憩入</button>',false);
    }

    public function test_休憩戻ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'leave_time' => null,
            'status' => 1
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $response = $this->post('rest');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('<button class="rest-button" type="submit" formaction="/rest">休憩戻</button>',false);

        $response = $this->post('/rest');
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる()
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

        $response = $this->post('rest');
        $response->assertStatus(302);

        $response = $this->post('/rest');
        $response->assertStatus(302);

        $response = $this->post('/rest');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('<button class="rest-button" type="submit" formaction="/rest">休憩戻</button>',false);
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる()
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

        $response = $this->post('/rest');
        $response->assertStatus(302);
        $this->assertDatabaseHas('rests', [
            'rest_start' => now()->format('H:i:s')
        ]);

        $response = $this->post('/rest');
        $response->assertStatus(302);
        $this->assertDatabaseHas('rests', [
            'rest_end' => now()->format('H:i:s')
        ]);


        $response = $this->get('/list');
        $response->assertStatus(200);
        $response->assertSee('00:00');
    }
}
