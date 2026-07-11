<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Laravel\Sanctum\Sanctum;

class sanctumTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_未認証時に書き込み系APIで401が返る()
    {
        $user = User::factory()->create();
        $bodyData = [
            'date' => '2026-01-01',
            'clock_in' => '10:00:00',
            'clock_out' => '20:00:00'
        ];

        $response = $this->postJson('/api/v1/attendance-records',$bodyData);
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->putJson('/api/v1/attendance-records/'.$attendance->id,$bodyData);
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);

        $response = $this->deleteJson('/api/v1/attendance-records/'.$attendance->id);
        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }

    public function test_認証済みユーザーは自分の勤怠を更新・削除できる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);
        Sanctum::actingAs($user);

        $bodyData = [
            'date' => '2026-01-01',
            'clock_in' => '10:00:00',
            'clock_out' => '20:00:00',
            'comment' => 'テスト'
        ];

        $response = $this->actingAs($user)->putJson('/api/v1/attendance-records/'.$attendance->id,$bodyData);
        $response->assertStatus(200);

        $response = $this->actingAs($user)->deleteJson('/api/v1/attendance-records/'.$attendance->id);
        $response->assertStatus(204);
    }

    public function test_他ユーザーの勤怠を更新・削除しようとすると403が返る()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create([
            'id' => 999
        ]);
        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $bodyData = [
            'date' => '2026-01-01',
            'clock_in' => '10:00:00',
            'clock_out' => '20:00:00'
        ];

        $response = $this->actingAs($user)->putJson('/api/v1/attendance-records/'.$otherAttendance->id,$bodyData);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'この操作を実行する権限がありません。'
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/attendance-records/'.$otherAttendance->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'この操作を実行する権限がありません。'
        ]);
    }
}
