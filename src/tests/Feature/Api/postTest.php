<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class postTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_POST_api_v1_attendance_recordsで勤怠が作成される()
    {
        $user = User::factory()->create();
        $bodyData = [
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ];

        $response = $this->actingAs($user,'sanctum')->postJson('/api/v1/attendance-records',$bodyData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances',[
            'attendance_date' => now()->toDateString(),
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00'
        ]);
    }

    public function test_バリデーションエラー時に422と日本語エラーメッセージが返る()
    {
        $user = User::factory()->create();
        $bodyData = [
            'date' => now()->toDateString(),
            'clock_in' => '',
            'clock_out' => '18:00:00'
        ];

        $response = $this->actingAs($user,'sanctum')->postJson('/api/v1/attendance-records',$bodyData);
        $response->assertStatus(422);
        $response->assertjson([
            'errors' => [
                'clock_in' => ['出勤時刻は必須です。']
            ]
        ]);
    }

    public function test_PUT_api_v1_attendance_records_attendanceRecordで勤怠が更新される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);
        $bodyData = [
            'date' => '2026-01-01',
            'clock_in' => '10:00:00',
            'clock_out' => '20:00:00',
            'comment' => 'テスト'
        ];

        $response = $this->actingAs($user,'sanctum')->putJson('/api/v1/attendance-records/'.$attendance->id,$bodyData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances',[
            'attendance_date' => '2026-01-01',
            'attendance_time' => '10:00:00',
            'leave_time' => '20:00:00'
        ]);

        $response = $this->putJson('/api/v1/attendance-records/999999',$bodyData);
        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。'
        ]);
    }

    public function test_DELETE_api_v1_attendance_records_attendanceRecordで勤怠が削除される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user,'sanctum')->deleteJson('/api/v1/attendance-records/'.$attendance->id);
        $response->assertStatus(204);

        $response = $this->deleteJson('/api/v1/attendance-records/999999');
        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。'
        ]);
    }
}
