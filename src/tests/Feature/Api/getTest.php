<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class getTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_GET_api_v1_attendance_recordsで勤怠一覧がJSONで取得できる()
    {
        $this->seed();
        $response = $this->getJson('/api/v1/attendance-records');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta'
        ]);
    }

    public function test_GET_api_v1_attendance_records_attendanceRecordで勤怠詳細がJSONで取得できる()
    {
        $attendance = Attendance::factory()->create([
            'attendance_date' => now()->toDateString(),
            'attendance_time' => '09:00:00',
            'leave_time' => '18:00:00'
        ]);
        $rest = Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => '12:00:00',
            'rest_end' => '13:00:00'
        ]);
        $response = $this->getJson('/api/v1/attendance-records/'.$attendance->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user',
                'breaks',
                'application',
            ]
        ]);
    }

    public function test_存在しないIDでは404とエラーJSONが返る()
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');
        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。'
        ]);
    }
}
