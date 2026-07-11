<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class clockInTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');
        $response->assertSee('<button class="attendance-button" type="submit" formaction="/attendance">出勤</button>',false);
        $response = $this->actingAs($user)->post('/attendance');
        $response->assertStatus(302);

        $response = $this->get('/');
        $response->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる()
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('<button class="attendance-button" type="submit" formaction="/attendance">出勤</button>',false);
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->post('/attendance');
        $response->assertStatus(302);

        $response = $this->actingAs($user)->get('list');
        $response->assertStatus(200);
        $response->assertSee(now()->format('H:i'));
    }
}
